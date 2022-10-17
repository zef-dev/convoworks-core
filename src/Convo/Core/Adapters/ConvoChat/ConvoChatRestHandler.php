<?php declare(strict_types=1);

namespace Convo\Core\Adapters\ConvoChat;

use Convo\Core\EventDispatcher\ServiceRunRequestEvent;
use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\Rest\RestSystemUser;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ConvoChatRestHandler implements RequestHandlerInterface
{
	/**
	 * @var \Convo\Core\Factory\ConvoServiceFactory
	 */
	private $_convoServiceFactory;

	/**
	 * @var \Convo\Core\IServiceDataProvider
	 */
	private $_convoServiceDataProvider;

	/**
	 * @var \Convo\Core\Params\IServiceParamsFactory
	 */
	private $_convoServiceParamsFactory;

	/**
	 * @var \Convo\Core\Factory\PlatformRequestFactory
	 */
	private $_platformRequestFactory;

	/**
	 * @var \Convo\Core\Util\IHttpFactory
	 */
	private $_httpFactory;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

    /*
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    private $_eventDispatcher;

	public function __construct( $logger, $httpFactory, $serviceFactory, $serviceDataProvider, $serviceParamsFactory, $platformRequestFactory, EventDispatcher $eventDispatcher)
	{
		$this->_logger						=	$logger;
		$this->_httpFactory					=	$httpFactory;
		$this->_convoServiceFactory			= 	$serviceFactory;
		$this->_convoServiceDataProvider	= 	$serviceDataProvider;
		$this->_convoServiceParamsFactory	= 	$serviceParamsFactory;
		$this->_platformRequestFactory  	=	$platformRequestFactory;
        $this->_eventDispatcher             =   $eventDispatcher;
	}

	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info	=	new \Convo\Core\Rest\RequestInfo( $request);

		$this->_logger->debug( 'Got info ['.$info.']');

		if ( $info->post() && $route = $info->route( 'service-run/convo_chat/{variant}/{serviceId}'))
		{
			$variant = $route->get('variant');
			$serviceId = $route->get('serviceId');

			$this->_logger->debug("Executing Convo Chat [$serviceId][$variant]");

			return $this->_handleConvoChatPathServiceIdPost($request, $variant, $serviceId);
		}

		if ( $info->post() && $route = $info->route( 'service-run/convo_chat/{variant}/{serviceId}'))
		{
			$variant = $route->get('variant');
			$serviceId = $route->get('serviceId');

			$this->_logger->debug("Executing convo_chat [$serviceId][$variant]");

			return $this->_handleConvoChatPathServiceIdPost($request, $variant, $serviceId);
		}

		throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
	}

	private function _handleConvoChatPathServiceIdPost(\Psr\Http\Message\ServerRequestInterface $request, $variant, $serviceId)
	{
		$owner		=	new RestSystemUser();
		$json		=	$request->getParsedBody();

		$text		=	$json['text'] ?? null;
		$is_init	=	$this->_isInit($json);
		$device_id	=	$json['device_id'];


// 		try {
// 			$meta	=	$this->_convoServiceDataProvider->getServiceMeta( $owner, $serviceId);
// 		} catch ( \Convo\Core\ComponentNotFoundException $e) {
// 			throw new \Convo\Core\Rest\NotFoundException( 'Service meta ['.$serviceId.'] not found', 0, $e);
// 		}

		try {
			$version_id			=	$this->_convoServiceFactory->getVariantVersion( $owner, $serviceId, 'convo_chat', $variant);
		} catch ( \Convo\Core\ComponentNotFoundException $e) {
			throw new \Convo\Core\Rest\NotFoundException( 'Service variant ['.$serviceId.']['.$variant.'] not found', 0, $e);
		}

		$this->_logger->debug( 'Got variant version ['.$variant.']['.$version_id.']');

		if ( empty( $version_id)) {
			throw new \Convo\Core\Rest\InvalidRequestException( 'Service ['.$serviceId.'] not published yet');
		}

		try {
			$platform_config	=	$this->_convoServiceDataProvider->getServicePlatformConfig( $owner, $serviceId, $version_id);
		} catch ( \Convo\Core\ComponentNotFoundException $e) {
			throw new \Convo\Core\Rest\NotFoundException( 'Service platform config ['.$serviceId.']['.$version_id.'] not found', 0, $e);
		}

		$this->_logger->debug( 'Got config ['.print_r( $platform_config, true).']');

		if ( !isset( $platform_config['convo_chat'])) {
		    throw new \Convo\Core\Rest\InvalidRequestException( 'Service ['.$serviceId.'] version ['.$version_id.'] is not enabled for platform ['.'convo_chat'.']');
		}

		$delegate_nlp		=	$platform_config['convo_chat']['delegateNlp'] ?? null;

		$service			=	$this->_convoServiceFactory->getService($owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);

		$text_request		=	new \Convo\Core\Adapters\ConvoChat\DefaultTextCommandRequest(
			$serviceId, $device_id, $device_id, $device_id, $text, $is_init, false /* <- temp */, $delegate_nlp, $json);
		$text_response		=	new \Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse();
		$text_response->setLogger($this->_logger);

		if ( $delegate_nlp) {
			// 		    TODO: load & use service owner account
			// 		    $service_meta     =   $this->_convoServiceDataProvider->getServiceMeta( $user, $service_id);
			// 		    $owner            =   $service_meta['owner'];
            $text_request     =   $this->_platformRequestFactory->toIntentRequest($text_request, $owner, $serviceId, $delegate_nlp);
		}

		$service->run($text_request, $text_response);

		$data	=	array(
				'service_state' => $service->getServiceState(),
		);

		$data		=	array_merge( $data, $text_response->getPlatformResponse());

        $this->_eventDispatcher->dispatch(
            new ServiceRunRequestEvent( false, $text_request, $text_response, $service, $variant),
            ServiceRunRequestEvent::NAME);

		return $this->_httpFactory->buildResponse( $data);
	}

	private function _isInit($json) {
	    $isInit = false;

        if (isset($json['lunch'])) {
            $isInit = $json['lunch'];
        } else if (isset($json['launch'])) {
            $isInit = $json['launch'];
        }

        return $isInit;
    }

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
