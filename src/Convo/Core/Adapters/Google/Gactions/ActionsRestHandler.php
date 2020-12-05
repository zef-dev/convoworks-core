<?php


namespace Convo\Core\Adapters\Google\Gactions;

use Convo\Core\Adapters\Google\Common\Elements\GoogleActionsElements;
use Convo\Core\Adapters\Google\Common\Intent\GoogleActionsIntentResolver;
use Convo\Core\ComponentNotFoundException;
use Convo\Core\ConvoServiceInstance;
use Convo\Core\Factory\ConvoServiceFactory;
use Convo\Core\IServiceDataProvider;
use Convo\Core\Params\IServiceParamsFactory;
use Convo\Core\Rest\InvalidRequestException;
use Convo\Core\Rest\NotFoundException;
use Convo\Core\Rest\RequestInfo;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Util\IHttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class ActionsRestHandler implements RequestHandlerInterface
{

    /**
     * @var ConvoServiceFactory
     */
    private $_convoServiceFactory;

    /**
     * @var IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    /**
     * @var IServiceParamsFactory
     */
    private $_convoServiceParamsFactory;

    /**
     * @var IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    public function __construct( LoggerInterface $logger, $httpFactory, $serviceFactory, $serviceDataProvider, $serviceParamsFactory)
    {
        $this->_logger						=	$logger;
        $this->_httpFactory					=	$httpFactory;
        $this->_convoServiceFactory			= 	$serviceFactory;
        $this->_convoServiceDataProvider	= 	$serviceDataProvider;
        $this->_convoServiceParamsFactory	= 	$serviceParamsFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RequestInfo */
        $info	=	new RequestInfo( $request);

        $this->_logger->debug( 'Google request info ['.$info->__toString().']');

        if ($info->post() && $route = $info->route( 'service-run/google-actions/{variant}/{serviceId}')) {
            $variant = $route->get('variant');
            $serviceId = $route->get('serviceId');

            $this->_logger->debug( 'Got service id ['.$serviceId.']['.$variant.']');
            return $this->_handleServiceRunPathGoogleActionsVariantPathServiceIdPost( $request, $variant, $serviceId);
        }

        throw new NotFoundException( 'Could not map ['.$info.']');
    }

    private function _handleServiceRunPathGoogleActionsVariantPathServiceIdPost(ServerRequestInterface $request, $variant, $serviceId)
    {
        $owner		=	new RestSystemUser();

        try {
            $version_id			=	$this->_convoServiceFactory->getVariantVersion( $owner, $serviceId, "google-actions", $variant);
        } catch ( ComponentNotFoundException $e) {
            throw new NotFoundException( 'Service variant ['.$serviceId.']['.$variant.'] not found', 0, $e);
        }

        $this->_logger->debug( 'Got variant version ['.$variant.']['.$version_id.']');

        if ( empty( $version_id)) {
            throw new InvalidRequestException( 'Service ['.$serviceId.'] not published yet');
        }

        $data      	 	=   $request->getParsedBody();

        $client 		=   new ActionsCommandRequest( $serviceId, $data);
        $client->init();

        $googleActionsIntentResolver = new GoogleActionsIntentResolver();
        $googleActionsElements = new GoogleActionsElements();

        $text_response = new ActionsCommandResponse($googleActionsIntentResolver, $googleActionsElements);

        /**  @var ConvoServiceInstance $service */
        $service = $this->_convoServiceFactory->getService( $owner, $serviceId, $version_id);
        $service->run( $this->_convoServiceParamsFactory, $client, $text_response);

        $json   		=   $text_response->getPlatformResponse();

        $this->_logger->debug( 'Going to return data ['.print_r( json_decode( $json, true), true).']');
        $this->_logger->debug('Going to return detected intent ['.$client->getText().']');

        return $this->_httpFactory->buildResponse( $json);
    }
}
