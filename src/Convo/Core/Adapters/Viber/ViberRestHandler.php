<?php


namespace Convo\Core\Adapters\Viber;


use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Rest\RestSystemUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ViberRestHandler implements RequestHandlerInterface
{
    /**
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    /**
     * @var \Convo\Core\Adapters\Fbm\FacebookAuthService
     */
    private $_facebookAuthService;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Factory\ConvoServiceFactory
     */
    private $_convoServiceFactory;

    /**
     * @var \Convo\Core\Params\IServiceParamsFactory
     */
    private $_convoServiceParamsFactory;

    /**
     * @var \Convo\Core\Factory\PlatformRequestFactory
     */
    private $_platformRequestFactory;

    /**
     * @var ViberApi
     */
    private $_viberApi;

    public function __construct($httpFactory, $logger, $adminUserDataProvider, $facebookAuthService, $convoServiceDataProvider, $convoServiceFactory, $convoServiceParamsFactory, $_platformRequestFactory)
    {
        $this->_logger				        = $logger;
        $this->_httpFactory			        = $httpFactory;
        $this->_adminUserDataProvider       = $adminUserDataProvider;
        $this->_facebookAuthService         = $facebookAuthService;
        $this->_convoServiceDataProvider    = $convoServiceDataProvider;
        $this->_convoServiceFactory         = $convoServiceFactory;
        $this->_convoServiceParamsFactory   = $convoServiceParamsFactory;
        $this->_platformRequestFactory      = $_platformRequestFactory;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $info   =   new \Convo\Core\Rest\RequestInfo( $request);

        if ( $info->post() && $route = $info->route( 'service-run/viber/{variant}/{serviceId}'))
        {
            return $this->_handleViberPathServiceIdPost( $request, $route->get('variant'), $route->get( 'serviceId'));
        }

        throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
    }

    private function _handleViberPathServiceIdPost($request, $variant, $serviceId) {
        return $this->_handleRequest($request, $variant, $serviceId);
    }

    private function _handleRequest($request, $variant, $serviceId) {
        $response = $this->_httpFactory->buildResponse(['EVENT_RECEIVED'], 200);
        $owner		=	new RestSystemUser();

        try {
            $version_id			=	$this->_convoServiceFactory->getVariantVersion( $owner, $serviceId, ViberCommandRequest::PLATFORM_ID, $variant);
        } catch ( \Convo\Core\ComponentNotFoundException $e) {
            throw new \Convo\Core\Rest\NotFoundException( 'Service variant ['.$serviceId.']['.$variant.'] not found', 0, $e);
        }

        $service 	=	$this->_convoServiceFactory->getService( $owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);
        $servicePlatformConfig = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $owner,
            $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        $viberCommandRequest = new ViberCommandRequest($this->_logger, $serviceId, $request->getParsedBody());
        $viberCommandRequest->init();

        if ($viberCommandRequest->isWebhookRequest()) {
            $servicePlatformConfig['viber']['webhook_build_status'] = IPlatformPublisher::SERVICE_PROPAGATION_STATUS_FINISHED;
            $this->_convoServiceDataProvider->updateServicePlatformConfig($owner, $serviceId, $servicePlatformConfig);
            $response = $this->_httpFactory->buildResponse(['EVENT_RECEIVED_AND_WEBHOOK_VERIFIED'], 200);
        } else if ($viberCommandRequest->isMessageRequest() || $viberCommandRequest->isSessionStart()) {
            $this->_viberApi = new ViberApi($this->_logger, $this->_httpFactory);
            $this->_viberApi->setupViberApi($owner, $serviceId, $servicePlatformConfig);

            $delegationNlp = $servicePlatformConfig["viber"]["delegateNlp"] ?? null;
            if ($delegationNlp) {
                $viberCommandRequest = $this->_platformRequestFactory->toIntentRequest($viberCommandRequest, $owner, $service, $delegationNlp);
                $debugData = print_r($viberCommandRequest->getPlatformData(), true);
                $this->_logger->info("Debug request with delegate [$debugData]");
            }

            $viberCommandResponse = new ViberCommandResponse();
            $service->run($viberCommandRequest, $viberCommandResponse);

            $viberCommandResponse->setSenderName($serviceId);
            $viberCommandResponse->setReceiver($viberCommandRequest->getSessionId());
            $this->_viberApi->callSendMessage($viberCommandResponse->getPlatformResponse());
        } else if ($viberCommandRequest->hasFailed()) {
            $servicePlatformConfig['viber']['webhook_build_status'] = IPlatformPublisher::SERVICE_PROPAGATION_STATUS_FINISHED;
            $this->_convoServiceDataProvider->updateServicePlatformConfig($owner, $serviceId, $servicePlatformConfig);
            $response = $this->_httpFactory->buildResponse(['AN_ERROR_OCCURRED'], 400);
        }

        return $response;
    }
}
