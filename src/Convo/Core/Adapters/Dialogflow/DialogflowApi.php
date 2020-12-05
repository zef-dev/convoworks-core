<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Dialogflow;

use Convo\Core\Util\SimpleFileResource;
use Google\ApiCore\ApiException;
use Google\Auth\OAuth2;
use Google\Cloud\Dialogflow\V2\Agent;
use Google\Cloud\Dialogflow\V2\AgentsClient;
use Google\Cloud\Dialogflow\V2\ExportAgentResponse;
use Google\Cloud\Dialogflow\V2\IntentsClient;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Psr\Http\Message\ResponseInterface;
use Convo\Core\Publish\IPlatformPublisher;

class DialogflowApi
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Data\Filesystem\FilesystemServiceDataProvider
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

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    private $_dialogflowAuthService;

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

    public function __construct($logger, $serviceDataProvider, \Convo\Core\IAdminUser $user, $serviceId, \Convo\Core\IAdminUserDataProvider $adminUserDataProvider, \Convo\Core\Util\IHttpFactory $httpFactory)
    {
        $this->_logger = $logger;
        $this->_convoServiceDataProvider = $serviceDataProvider;

        $this->_user = $user;
        $this->_serviceId = $serviceId;

        $this->_projectId = $this->_getProjectId();
        $this->_adminUserDataProvider = $adminUserDataProvider;
        $this->_httpFactory = $httpFactory;

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
			$operation_res = $client->exportAgent($project);
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

    public function analyzeText($text, $locale)
    {
       return $this->_performDialogflowDetectIntentRequest($text, $locale);
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

    public function validateAgent($locale) {
        return $this->_performDialogflowAgentValidationRequest($locale);
    }

    private function _performDialogflowDetectIntentRequest($text, $locale) {
        $sessid = uniqid();

        $accessToken = $this->_dialogflowAuthService->provideDialogflowAccessToken();
        $this->_logger->debug("Analyzing text [$text] with project id [".$this->_projectId."][$sessid]");

        $httpClient = $this->_httpFactory->getHttpClient();

        $url = self::BASE_DIALOGFLOW_URL . '/projects/'.$this->_projectId.'/agent/sessions/'.$sessid.':detectIntent';
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ];
        $body = [
            "queryInput" => [
                "text" => [
                    "text" => $text,
                    "languageCode" => $locale
                ]
            ]
        ];
        $dialogflowDetectIntentRequest = $this->_httpFactory->buildRequest('POST', $url, $headers, $body);

        /** @var ResponseInterface $dialogflowDetectIntentResponse */
        $dialogflowDetectIntentResponse = $httpClient->sendRequest($dialogflowDetectIntentRequest);

        return $dialogflowDetectIntentResponse->getBody()->getContents();
    }

    private function _performDialogflowAgentValidationRequest( $locale) {
        $accessToken = $this->_dialogflowAuthService->provideDialogflowAccessToken();
        $this->_logger->debug("Validating agent with [$locale]");

        $httpClient = $this->_httpFactory->getHttpClient();

        $url = self::BASE_DIALOGFLOW_URL . '/projects/'.$this->_projectId.'/agent/validationResult?languageCode='.$locale;
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ];
        $dialogflowDetectIntentRequest = $this->_httpFactory->buildRequest('GET', $url, $headers);

        /** @var ResponseInterface $dialogflowValidateAgentResponse */
        $dialogflowValidateAgentResponse = $httpClient->sendRequest($dialogflowDetectIntentRequest);
        $validationResult = $dialogflowValidateAgentResponse->getBody()->getContents();

        $this->_logger->warning('Dialogflow Agent Validation Result [' . $validationResult . "]");

        $dialogflowAgentValidationErrors = json_decode($validationResult, true);
        $errorMessage = "";
        foreach ($dialogflowAgentValidationErrors['validationErrors'] as $validationError) {
            $this->_logger->debug('Printing error object [' . print_r($validationError, true) . ']');
            if (in_array($validationError['severity'], self::DIALOGFLOW_AGENT_VALIDATION_EXCEPTION_TRIGGERS) ) {
                $errorMessage .= $validationError['errorMessage'] . " ";
            }
        }

        if (!empty($errorMessage)) {
            throw new \Exception($errorMessage);
        }

        return $validationResult;
    }

    // UTIL
    private function _setUpClients()
    {
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user,
            $this->_serviceId,
            IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );

        if (empty($config['dialogflow']['serviceAccount'])) {
            throw new \Exception('Service account data missing. Can not use API');
        }

        $auth = json_decode($config['dialogflow']['serviceAccount'], true);
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

		if (empty($config['dialogflow']['serviceAccount'])) {
			throw new \Exception('Service account data missing. Can not use API');
		}

		$auth = json_decode($config['dialogflow']['serviceAccount'], true);
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

        if (empty($config['dialogflow']['serviceAccount'])) {
            throw new \Exception('Service account data missing. Can not get Project ID');
        }

        return json_decode($config['dialogflow']['serviceAccount'], true)['project_id'];
    }

    public function __toString()
    {
        return get_class($this).'[]';
    }
}
