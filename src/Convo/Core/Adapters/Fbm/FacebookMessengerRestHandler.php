<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Fbm;

use Convo\Core\Admin\AdminUser;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Rest\RestSystemUser;
use Psr\Http\Server\RequestHandlerInterface;

class FacebookMessengerRestHandler implements RequestHandlerInterface
{
    /**
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;

    /**
     * @var \Convo\Core\Adapters\Fbm\FacebookAuthService
     */
    private $_facebookAuthService;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

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
     * @var FacebookMessengerApiFactory
     */
    private $_facebookMessengerApiFactory;

    public function __construct($httpFactory, $logger, $adminUserDataProvider, $facebookAuthService, $convoServiceDataProvider, $convoServiceFactory, $convoServiceParamsFactory, $_platformRequestFactory, $facebookMessengerApiFactory)
    {
    	$this->_logger				        = $logger;
    	$this->_httpFactory			        = $httpFactory;
        $this->_adminUserDataProvider       = $adminUserDataProvider;
        $this->_facebookAuthService         = $facebookAuthService;
        $this->_convoServiceDataProvider    = $convoServiceDataProvider;
        $this->_convoServiceFactory         = $convoServiceFactory;
        $this->_convoServiceParamsFactory   = $convoServiceParamsFactory;
        $this->_platformRequestFactory      = $_platformRequestFactory;
        $this->_facebookMessengerApiFactory = $facebookMessengerApiFactory;
    }

    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
    	$info   =   new \Convo\Core\Rest\RequestInfo( $request);

    	if ( $info->get() && $route = $info->route( 'service-run/facebook_messenger/{variant}/{serviceId}'))
    	{
    		return $this->_handleAdminAuthPathFacebookPathServiceIdGet( $request, $route->get( 'serviceId'));
    	}

    	if ( $info->post() && $route = $info->route( 'service-run/facebook_messenger/{variant}/{serviceId}'))
    	{
    		return $this->_handleAdminAuthPathFacebookPathServiceIdPost( $request, $route->get('variant'), $route->get( 'serviceId'));
    	}

    	throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
    }

    private function _handleAdminAuthPathFacebookPathServiceIdGet(\Psr\Http\Message\ServerRequestInterface $request, $serviceId)
    {
        $meta = $this->_convoServiceDataProvider->getServiceMeta(
            new AdminUser('', '', '', '', ''), // todo @tole why does this method need an AdminUser when it doesn't do anything with it?
            $serviceId
        );

        $owner = $meta['owner'] ?? null;

        if (!$owner) {
            throw new \Exception("Service [$serviceId] has no owner.");
        }

        $user = $this->_adminUserDataProvider->findUser($owner);

        $config = $this->_convoServiceDataProvider->getServicePlatformConfig(
        $user,
        $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        $verify_token 	= 	$config['facebook_messenger']['webhook_verify_token'];

        $params			=	$request->getQueryParams();
        $this->_logger->info("Params [" . print_r($params, true) . "]");
        $this->_logger->info("Verify Token [" . $verify_token . "]");
        $mode 			=	$params['hub_mode'] ?? null;
        $token			=	$params['hub_verify_token'] ?? null;
        $challenge		=	$params['hub_challenge'] ?? null;

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === $verify_token) {
                $this->_logger->debug('Verified webhook from FB');

                return $this->_httpFactory->buildResponse($challenge);
            } else {
                return $this->_httpFactory->buildResponse([], 403);
            }
        }

        return $this->_httpFactory->buildResponse([], 400);
    }

    private function _handleAdminAuthPathFacebookPathServiceIdPost(\Psr\Http\Message\ServerRequestInterface $request, $variant, $serviceId)
    {
        // request verification
        $errorResponse = $this->_verifyRequest($serviceId, $request);
        if ($errorResponse === null) {
            $this->_logger->info("Messenger request verified.");
            $this->_handleRequest($variant, $serviceId, $request);
            return $this->_httpFactory->buildResponse('EVENT_RECEIVED');
        } else {
          $this->_logger->warning("Messenger request not verified.");
            return $errorResponse;
        }
    }

    /**
     * Verifies the request.
     *
     * @param $serviceId
     * @param $request
     * @return bool|\Psr\Http\Message\ResponseInterface
     * @throws \Convo\Core\DataItemNotFoundException
     */
    private function _verifyRequest($serviceId, $request) {
        $errorResponse = null;

        if (empty($request->getHeader('X-Hub-Signature'))) {
            $errorResponse = $this->_httpFactory->buildResponse([], 400);
        }

        $signature = $request->getHeaderLine('X-Hub-Signature');

        $this->_logger->info("Signature as array [" . $signature . "]");
        $meta = $this->_convoServiceDataProvider->getServiceMeta(
            new AdminUser('', '', '', '', ''), // todo @tole why does this method need an AdminUser when it doesn't do anything with it?
            $serviceId
        );

        $owner = $meta['owner'] ?? null;

        if (!$owner) {
          $errorResponse =  $this->_httpFactory->buildResponse(["message" => "Service [$serviceId] has no owner."], 400);
        }

        $user = $this->_adminUserDataProvider->findUser($owner);

        $config = $this->_convoServiceDataProvider->getServicePlatformConfig(
        $user,
        $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        $appsecret = $config['facebook_messenger']['app_secret'];

        if (!$this->_facebookAuthService->verifyPayloadVerity($appsecret, $signature, json_encode($request->getParsedBody()))) {
            $errorResponse =  $this->_httpFactory->buildResponse([], 400);
        }

        return $errorResponse;
    }

    /**
     * Handles the request and sends and response to facebook send api.
     *
     * @param $serviceId
     * @param $request
     * @throws \Exception
     */
    private function _handleRequest($variant, $serviceId, $request) {
        $owner		=	new RestSystemUser();

        try {
            $version_id			=	$this->_convoServiceFactory->getVariantVersion( $owner, $serviceId, FacebookMessengerCommandRequest::PLATFORM_ID, $variant);
        } catch ( \Convo\Core\ComponentNotFoundException $e) {
            throw new \Convo\Core\Rest\NotFoundException( 'Service variant ['.$serviceId.']['.$variant.'] not found', 0, $e);
        }

        $service 	=	$this->_convoServiceFactory->getService( $owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);
        $servicePlatformConfig = $this->_convoServiceDataProvider->getServicePlatformConfig(
            new RestSystemUser(),
            $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        $messenger_request = new FacebookMessengerCommandRequest($this->_logger, $serviceId, $request->getParsedBody());
        /** @var FacebookMessengerApi $facebookMessengerApi */
        $facebookMessengerApi = $this->_facebookMessengerApiFactory->getApi($owner, $serviceId, $this->_convoServiceDataProvider);
        foreach($messenger_request->getPlatformData()['entry'] as $entry) {
            $messenger_request->setEntry($entry);
            $messenger_request->init();

            $messenger_response = new FacebookMessengerCommandResponse();
            $delegationNlp = $servicePlatformConfig["facebook_messenger"]["delegateNlp"] ?? null;

            if ($delegationNlp) {
                $messenger_request = $this->_platformRequestFactory->toIntentRequest($messenger_request, $owner, $serviceId, $delegationNlp);
                $debugData = print_r($messenger_request->getPlatformData(), true);
                $this->_logger->info("Debug request with delegate [$debugData]");
            }

            $service->run($messenger_request, $messenger_response);

            $senderId = $entry["messaging"][0]["sender"]["id"];
            if (count($messenger_response->getTexts()) > 0) {
              foreach ($messenger_response->getTexts() as $text) {
                $messenger_response->setText($text);
                $data = $messenger_response->getPlatformResponse();
                $facebookMessengerApi->callSendApi($senderId, $data);
              }
            }
        }
    }
}
