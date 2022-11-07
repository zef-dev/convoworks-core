<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Dialogflow;

use Convo\Core\Util\SimpleFileResource;
use Google\ApiCore\ApiException;
use Google\Auth\OAuth2;
use Google\Cloud\Dialogflow\V2\Agent;
use Google\Cloud\Dialogflow\V2\AgentsClient;
use Google\Cloud\Dialogflow\V2\ExportAgentResponse;
use Google\Cloud\Dialogflow\V2\IntentsClient;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Convo\Core\Publish\IPlatformPublisher;

class DialogflowApi
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    /**
     * @var \Convo\Core\IAdminUser
     */
    private $_user;

    /**
     * @var string
     */
    private $_serviceId;

    private $_projectId;

    /**
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;

    private $_dialogflowAuthService;

    private $_targetPlatformId;

    private const BASE_DIALOGFLOW_URL = 'https://dialogflow.googleapis.com/v2';

    private const DIALOGFLOW_AGENT_VALIDATION_EXCEPTION_TRIGGERS = ["ERROR", "CRITICAL", "SEVERITY_UNSPECIFIED"];

    /**
     * Map of clients to use
     * @var array
     */
    private $_clients = [
        'agents' => null,
        'intents' => null,
        'sessions' => null
    ];

    public function __construct($logger, $serviceDataProvider, \Convo\Core\IAdminUser $user, $serviceId, \Convo\Core\IAdminUserDataProvider $adminUserDataProvider, $platformId)
    {
        $this->_logger = $logger;
        $this->_convoServiceDataProvider = $serviceDataProvider;

        $this->_user = $user;
        $this->_serviceId = $serviceId;
        $this->_targetPlatformId = $platformId;

        $this->_projectId = $this->_getProjectId();
        $this->_adminUserDataProvider = $adminUserDataProvider;

        $this->_dialogflowAuthService = $this->_setupAuthService();
        $this->_clients = $this->_setUpClients();
    }

    /**
     * Gets existing agent for project
     * @return Agent
     */
    public function getAgent()
    {
        /** @var \Google\Cloud\Dialogflow\V2\AgentsClient $client */
        $client = $this->_clients['agents'];

        $project = $client->projectName($this->_projectId);
        $agent = null;

        try {
            $agent = $client->getAgent($project);
        } catch (ApiException $e) {
            $this->_logger->notice($e->getMessage());
        } finally {
            $client->close();
        }

        return $agent;
    }

    public function setAgent(Agent $agent)
    {
        /** @var \Google\Cloud\Dialogflow\V2\AgentsClient $client */
        $client = $this->_clients['agents'];

        $project = $client->projectName($this->_projectId);
        $agent->setParent($project);

        try {
            $client->setAgent($agent);
        } finally {
            $client->close();
        }
    }

    public function deleteAgent()
    {
        /** @var \Google\Cloud\Dialogflow\V2\AgentsClient $client */
        $client = $this->_clients['agents'];

        $project = $client->projectName($this->_projectId);

        try {
            $client->deleteAgent($project);
        } finally {
            $client->close();
        }
    }

    public function export($serviceId)
	{
		/** @var \Google\Cloud\Dialogflow\V2\AgentsClient $client */
		$client = $this->_clients['agents'];

		$project = $client->projectName($this->_projectId);

		try {
			$operation_res = $client->exportAgent($project, "");
			$operation_res->pollUntilComplete();

			/** @var ExportAgentResponse $result */
			$result = $operation_res->getResult();
			$file = new SimpleFileResource(
				$serviceId.'.zip', 'application/zip', $result->getAgentContent()
			);
			return $file;
		} finally {
			$client->close();
		}
	}

    public function restore($zipBytes)
    {
        /** @var \Google\Cloud\Dialogflow\V2\AgentsClient $client */
        $client = $this->_clients['agents'];

        $project = $client->projectName($this->_projectId);

        try {
            $client->restoreAgent($project, [
                'agentContent' => $zipBytes
            ]);
        } finally {
            $client->close();
        }
    }

    public function import($zipBytes)
    {
        /** @var \Google\Cloud\Dialogflow\V2\AgentsClient $client */
        $client = $this->_clients['agents'];

        $project = $client->projectName($this->_projectId);

        try {
            $client->importAgent($project, [
                'agentContent' => $zipBytes
            ]);
        } finally {
            $client->close();
        }
    }

    /**
     * @param $text
     * @param $locale
     * @return false|string
     * @throws \Exception
     */
    public function analyzeText($text, $locale) {
        /** @var SessionsClient $client */
        $client = $this->_clients['sessions'];
        $response = [];
        $this->_logger->debug("Going to analyze text...");
        try {
            $sessionName = $client->sessionName($this->_projectId, uniqid());
            $this->_logger->debug("Going to prepare TextInput...");
            $textInput = new TextInput();

            $textInput->setText($text);
            $textInput->setLanguageCode($locale);

            $this->_logger->debug("Going to prepare QueryInput...");
            $queryInput = new QueryInput();
            $queryInput->setText($textInput);
            $this->_logger->debug("Going to detect intent...");
            $queryResult = $client->detectIntent($sessionName, $queryInput)->getQueryResult();

            if (!empty($queryResult)) {
                $response['queryResult']['intent']['displayName'] = $queryResult->getIntent()->getDisplayName();
                $response['queryResult']['parameters'] = json_decode($queryResult->getParameters()->serializeToJsonString(), true);
            } else {
                throw new \Exception("Couldn't prepare query result.");
            }
        } catch (ApiException $e) {
            throw new \Exception($e->getMessage());
        } finally {
            $client->close();
        }

        return json_encode($response);
    }

    public function trainAgent() {
        /** @var \Google\Cloud\Dialogflow\V2\AgentsClient $client */
        $client = $this->_clients['agents'];

        $project = $client->projectName($this->_projectId);

        try {
            $client->trainAgent($project);
        } finally {
            $client->close();
        }
    }

    // UTIL
    private function _setUpClients()
    {
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user,
            $this->_serviceId,
            IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );

        if (empty($config[$this->_targetPlatformId]['serviceAccount'])) {
            throw new \Exception('Service account data missing. Can not use API');
        }

        $auth = json_decode($config[$this->_targetPlatformId]['serviceAccount'], true);
        $config = ['credentials' => $auth];

        return [
            'agents' => new AgentsClient($config),
            'intents' => new IntentsClient($config),
            'sessions' => new SessionsClient($config)
        ];
    }

    private function _setupAuthService()
	{
		$config = $this->_convoServiceDataProvider->getServicePlatformConfig(
			$this->_user,
			$this->_serviceId,
		    IPlatformPublisher::MAPPING_TYPE_DEVELOP
		);

		if (empty($config[$this->_targetPlatformId]['serviceAccount'])) {
			throw new \Exception('Service account data missing. Can not use API');
		}

		$auth = json_decode($config[$this->_targetPlatformId]['serviceAccount'], true);
		$config = ['credentials' => $auth];

		return new DialogflowAuthService(new OAuth2([]), $this->_user, $config['credentials'], $this->_adminUserDataProvider);
	}

    private function _getProjectId()
    {
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user,
            $this->_serviceId,
            IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );

        if (empty($config[$this->_targetPlatformId]['serviceAccount'])) {
            throw new \Exception('Service account data missing. Can not get Project ID');
        }

        return json_decode($config[$this->_targetPlatformId]['serviceAccount'], true)['project_id'];
    }

    public function __toString()
    {
        return get_class($this).'[]';
    }
}
