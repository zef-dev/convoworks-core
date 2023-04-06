<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Dialogflow;

use Convo\Core\Util\IHttpFactory;
use Convo\Core\Publish\IPlatformPublisher;
use Psr\Http\Client\ClientExceptionInterface;

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

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    private $_httpClient;

    private const BASE_DIALOGFLOW_URL = 'https://dialogflow.googleapis.com/v2';

    private const CREATE_VERSION_MAX_RETRY_COUNT = 20;

    private const DIALOGFLOW_AGENT_VALIDATION_EXCEPTION_TRIGGERS = ["ERROR", "CRITICAL", "SEVERITY_UNSPECIFIED"];

    public function __construct($logger, $serviceDataProvider, \Convo\Core\IAdminUser $user, $serviceId, \Convo\Core\IAdminUserDataProvider $adminUserDataProvider, $platformId, $httpFactory)
    {
        $this->_logger = $logger;
        $this->_convoServiceDataProvider = $serviceDataProvider;

        $this->_user = $user;
        $this->_serviceId = $serviceId;
        $this->_targetPlatformId = $platformId;
        $this->_httpFactory = $httpFactory;
        $this->_httpClient = $this->_httpFactory->getHttpClient();

        $this->_projectId = $this->_getProjectId();
        $this->_adminUserDataProvider = $adminUserDataProvider;

        $this->_dialogflowAuthService = $this->_setupAuthService();
    }

    /**
     * Gets existing agent for project
     * @return []
     */
    public function getAgent()
    {
        $agent = null;
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent";

        try {
            $response = $this->_executeRequest(
                IHttpFactory::METHOD_GET,
                $url
            );
            $agent = json_decode($response->getBody()->__toString(), true);
        } catch (ClientExceptionInterface $e) {
            $this->_logger->notice($e->getMessage());
        }

        return $agent;
    }

    public function setAgent($agent)
    {
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent";

        $agent['parent'] = 'projects/'.$this->_projectId;
        $response = $this->_executeRequest(
            IHttpFactory::METHOD_POST,
            $url,
            [],
            $agent
        );

        return json_decode($response->getBody()->__toString(), true);
    }

    public function deleteAgent()
    {
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent";

        $response = $this->_executeRequest(
            IHttpFactory::METHOD_DELETE,
            $url
        );

        return json_decode($response->getBody()->__toString(), true);
    }

    public function restore($zipBytes)
    {
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent:restore";

        $response = $this->_executeRequest(
            IHttpFactory::METHOD_POST,
            $url,
            [],
            [
                "agentContent" => base64_encode($zipBytes)
            ]
        );

        return json_decode($response->getBody()->__toString(), true);
    }

    /**
     * @param $text
     * @param $locale
     * @return false|string
     * @throws \Exception
     */
    public function analyzeText($text, $locale, $variant = '-') {
        $someSessionIdValue = '0123456789';
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent/environments/".$variant.'/users/'.$this->_user->getId().'/sessions/'.$someSessionIdValue.':detectIntent';

        $result = $this->_executeRequest(
            IHttpFactory::METHOD_POST,
            $url,
            [],
            [
                "queryInput" => [
                    "text" => [
                        "text" => $text,
                        "languageCode" => $locale
                    ]
                ]
            ]
        );


        $response = [];
        $this->_logger->debug("Going to analyze text...");
        $result = json_decode($result->getBody()->__toString(), true);
        if (!empty($result)) {
            $response['queryResult']['intent']['displayName'] = $result['queryResult']['intent']['displayName'] ?? '';
            $response['queryResult']['parameters'] = $result['queryResult']['parameters'] ?? [];
        }

        return json_encode($response);
    }

    public function trainAgent() {
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent:train";

        $response = $this->_executeRequest(IHttpFactory::METHOD_POST, $url);

        return json_decode($response->getBody()->__toString(), true);
    }

    public function getVersion($versionId) {
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent/versions/".$versionId;

        $response = $this->_executeRequest(IHttpFactory::METHOD_GET, $url);

        return json_decode($response->getBody()->__toString(), true);
    }
    public function createVersion($versionId = '') {

        $this->_logger->info('Going to create a new Dialogflow Agent Version...');
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent/versions";

        if (!empty($versionId)) {
            $response = $this->_executeRequest(
                IHttpFactory::METHOD_POST,
                $url,
                [],
                [
                    "description" => $versionId
                ]
            );
        } else {
            $response = $this->_executeRequest(IHttpFactory::METHOD_POST, $url);
        }

        $createdDialogflowAgentVersion = json_decode($response->getBody()->__toString(), true);
        $dialogflowAgentVersion = null;
        $retryCount = 1;

        do {
            if (self::CREATE_VERSION_MAX_RETRY_COUNT === $retryCount) {
                break;
            }
            $dialogflowAgentVersion = $this->getVersion($createdDialogflowAgentVersion['versionNumber']);
            $retryCount++;
        } while ($dialogflowAgentVersion['status'] === 'IN_PROGRESS');

        $this->_logger->debug('Got Dialogflow Agent Version ['.json_encode($dialogflowAgentVersion).']');

        return $dialogflowAgentVersion;
    }


    public function getEnvironment($environmentId) {
        $environment = null;
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent/environments/$environmentId";

        try {
            $response = $this->_executeRequest(IHttpFactory::METHOD_GET, $url);
            $environment = json_decode($response->getBody()->__toString(), true);
        } catch (ClientExceptionInterface $e) {
            $this->_logger->notice($e);
        }

        return $environment;
    }

    public function createEnvironment($environmentId, $agentVersion) {
        $url = self::BASE_DIALOGFLOW_URL."/projects/$this->_projectId/agent/environments?environmentId=$environmentId";

        $payload["agentVersion"] = "projects/$this->_projectId/agent/versions/$agentVersion";

        $response = $this->_executeRequest(IHttpFactory::METHOD_POST, $url, [], $payload);

        return json_decode($response->getBody()->__toString(), true);
    }

    public function loadVersionIntoEnvironment($environmentId, $agentVersion, $allowLoadToDraftAndDiscardChanges = false) {

        $this->_logger->info('Going to load version ['.$agentVersion.'] into environment with id ['.$environmentId.']');
        $url = self::BASE_DIALOGFLOW_URL.'/projects/'.$this->_projectId.'/agent/environments/'.$environmentId.'?updateMask=agentVersion';

        $payload = ['agentVersion' => 'projects/'.$this->_projectId.'/agent/versions/'.$agentVersion];

        if (is_bool($allowLoadToDraftAndDiscardChanges) && $allowLoadToDraftAndDiscardChanges && $environmentId === '-') {
            $url.= "&allowLoadToDraftAndDiscardChanges=true";
        }

        $this->_logger->info('JSON Bondy ['.json_encode($payload).']');

        $response = $this->_executeRequest(IHttpFactory::METHOD_PATCH, $url, [], $payload);

        return json_decode($response->getBody()->__toString(), true);
    }

    // UTIL

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

		return new DialogflowAuthService($this->_logger, $this->_httpFactory, $this->_user, $config['credentials'], $this->_adminUserDataProvider, $this->_targetPlatformId);
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

    // UTIL

    /**
     * @param $method
     * @param $url
     * @param $headers
     * @param $body
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    private function _executeRequest($method, $url, $headers = [], $body = null)
    {
        $accessToken = $this->_dialogflowAuthService->provideDialogflowAccessToken();

        $request = $this->_httpFactory->buildRequest(
            $method,
            $url,
            $headers,
            $body
        );
        $request  = $request->withHeader('Authorization', $accessToken);

        return $this->_httpClient->sendRequest($request);
    }

    public function __toString()
    {
        return get_class($this).'[]';
    }
}
