<?php


namespace Convo\Core\Admin;


use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\IServiceDataProvider;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Rest\RequestInfo;
use Convo\Core\Rest\ServiceEnablementException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AmazonAlexaSkillInfo implements \Psr\Http\Server\RequestHandlerInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Convo\Core\Adapters\Alexa\AmazonPublishingService
     */
    private $_amazonPublishingService;

    /**
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;

    /**
     * @var IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    public function __construct($logger, $httpFactory, $adminUserDataProvider, $amazonPublishingService, $convoServiceDataProvider)
    {
        $this->_logger                   = $logger;
        $this->_httpFactory              = $httpFactory;
        $this->_adminUserDataProvider    = $adminUserDataProvider;
        $this->_amazonPublishingService  = $amazonPublishingService;
        $this->_convoServiceDataProvider = $convoServiceDataProvider;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $json = $request->getParsedBody();
        $info = new RequestInfo($request);

        $user = $this->_adminUserDataProvider->findUser($json['owner']);

        if ($info->post() && $route = $info->route('get-existing-alexa-skill/{serviceId}/manifest'))
        {
            return $this->_httpFactory->buildResponse($this->_getAlexaSkill($user, $route->get('serviceId')));
        }

        if ($info->post() && $route = $info->route('get-existing-alexa-skill/{serviceId}/account-linking-information'))
        {
            return $this->_httpFactory->buildResponse($this->_getAlexaSkillAccountLinkingInformation($user, $route->get('serviceId')));
        }

		if ($info->put() && $route = $info->route('get-existing-alexa-skill/{serviceId}/enable-alexa-skill-for-test'))
		{
			return $this->_httpFactory->buildResponse($this->_enableAlexaSkillForTest($user, $route->get('serviceId')));
		}

        throw new \Convo\Core\Rest\NotFoundException('Could not map info ['.$info.']');
    }

    private function _getAlexaSkill($user, $serviceId) {
        $amazon_config = $this->_getAmazonPlatformConfigFromService($user, $serviceId);
        $skillId = $amazon_config['app_id'];

        return $this->_amazonPublishingService->getSkill($user, $skillId, 'development');
    }
    private function _getAlexaSkillAccountLinkingInformation($user, $serviceId) {
        $amazon_config = $this->_getAmazonPlatformConfigFromService($user, $serviceId);
        $skillId = $amazon_config['app_id'];

        return $this->_amazonPublishingService->getAccountLinkingInformation($user, $skillId, 'development');
    }

	private function _enableAlexaSkillForTest($user, $serviceId) {
		$data = ["can_be_enabled_for_testing" => false];
		$platform_config = $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
		$skillId = $platform_config[AmazonCommandRequest::PLATFORM_ID]['app_id'];
		$enabledForTest = $platform_config[AmazonCommandRequest::PLATFORM_ID]['enabled_for_test'] ?? false;

		// check if the skill was enabled
		try {
			if (!$enabledForTest) {
				$statusCodeOfEnablementCheck = $this->_amazonPublishingService->checkSkillEnablementStatus($user, $skillId, 'development');
			} else {
				$statusCodeOfEnablementCheck = 204;
			}
		} catch (ClientExceptionInterface $e) {
			$statusCodeOfEnablementCheck = $e->getCode();

			if ($statusCodeOfEnablementCheck === 404) {
				$this->_logger->info("Seems that the Alexa Skill is not enabled. Going to enable Alexa Skill [" . $skillId . "]..." );
			} else {
				throw new ServiceEnablementException($e->getMessage());
			}
		}

		// enable the skill if not enabled
		try {
			if ($statusCodeOfEnablementCheck === 404) {
				$this->_amazonPublishingService->enableSkillForUse($user, $skillId, 'development');
				$data = ["can_be_enabled_for_testing" => true];
			}

			if (!$enabledForTest) {
				$platform_config[AmazonCommandRequest::PLATFORM_ID]['enabled_for_test'] = true;
				$this->_convoServiceDataProvider->updateServicePlatformConfig($user, $serviceId, $platform_config);
			}
		} catch (ClientExceptionInterface $e) {
			throw new ServiceEnablementException($e->getMessage());
		}

		return $data;
	}

    private function _getAmazonPlatformConfigFromService($user, $serviceId) {
        $platform_config = $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        return $platform_config[AmazonCommandRequest::PLATFORM_ID];
    }
}
