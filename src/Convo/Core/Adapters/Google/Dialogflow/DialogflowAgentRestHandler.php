<?php


namespace Convo\Core\Adapters\Google\Dialogflow;

use Convo\Core\ComponentNotFoundException;
use Convo\Core\ConvoServiceInstance;
use Convo\Core\Factory\ConvoServiceFactory;
use Convo\Core\IServiceDataProvider;
use Convo\Core\Params\IServiceParamsFactory;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Params\RequestParamsScope;
use Convo\Core\Rest\InvalidRequestException;
use Convo\Core\Rest\NotFoundException;
use Convo\Core\Rest\RequestInfo;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Util\IHttpFactory;
use Psr\Http\Server\RequestHandlerInterface;
use \Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Log\LoggerInterface;

class DialogflowAgentRestHandler implements RequestHandlerInterface
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
        if ( $info->post() && $route = $info->route( 'service-run/dialogflow/{variant}/{serviceId}'))
        {
            $variant = $route->get('variant');
            $serviceId = $route->get('serviceId');

            $this->_logger->debug( 'Got service id ['.$serviceId.']['.$variant.']');
            return $this->_handleServiceRunPathGoogleAssistantPathVariantPathServiceIdPost( $request, $variant, $serviceId, "dialogflow");
        }

        throw new NotFoundException( 'Could not map ['.$info.']');
    }

    private function _handleServiceRunPathGoogleAssistantPathVariantPathServiceIdPost(ServerRequestInterface $request, $variant, $serviceId, $type)
    {
        $owner		=	new RestSystemUser();
        $platform_id = $type;

        try {
            $version_id			=	$this->_convoServiceFactory->getVariantVersion( $owner, $serviceId, $platform_id, $variant);
        } catch ( ComponentNotFoundException $e) {
            throw new NotFoundException( 'Service variant ['.$serviceId.']['.$variant.'] not found', 0, $e);
        }

        $this->_logger->debug( 'Got variant version ['.$variant.']['.$version_id.']');

        if ( empty( $version_id)) {
            throw new InvalidRequestException( 'Service ['.$serviceId.'] not published yet');
        }

        $data      	 	=   $request->getParsedBody();

        $client 		=   new DialogflowCommandRequest( $serviceId, $data);

        $client->init();

        $scope		=	new RequestParamsScope( $client, IServiceParamsScope::SCOPE_TYPE_SESSION, IServiceParamsScope::LEVEL_TYPE_SERVICE);
        $serviceParams = $this->_convoServiceParamsFactory->getServiceParams( $scope);

        $text_response = new DialogflowCommandResponse($serviceParams, $client);
        $text_response->setLogger($this->_logger);
        if ($client->isRePromptRequest()) {
            $serviceParams->setServiceParam('__finalReprompt', false);
            if (!$serviceParams->getServiceParam('__keepRePrompt')) {
                $serviceParams->setServiceParam('__reprompt', '');
            }

            $arguments = $client->getPlatformData()['originalDetectIntentRequest']['payload']['inputs'][0]['arguments'];
            if ($arguments[0]['name'] === "REPROMPT_COUNT" && $arguments[0]['intValue'] == 1) {
                $serviceParams->setServiceParam('__reprompt', '');
            }

            if ($arguments[1]['name'] === "IS_FINAL_REPROMPT" && $arguments[1]['boolValue']) {
                $serviceParams->setServiceParam('__finalReprompt', true);
            }

            /** @var DialogflowRePromptResponse $dialogflowRePromptResponse */
            $dialogflowRePromptResponse = new DialogflowRePromptResponse($serviceParams);
            return $this->_httpFactory->buildResponse($dialogflowRePromptResponse->getPlatformResponse());
        } else {
            $serviceParams->setServiceParam('__keepRePrompt', false);

            /**  @var ConvoServiceInstance $service */
            $service = $this->_convoServiceFactory->getService( $owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);
            $service->run($client, $text_response);

            $json   		=   $text_response->getPlatformResponse();

            $this->_logger->debug( 'Going to return data ['.print_r( json_decode( $json, true), true).']');
            $this->_logger->debug('Going to return detected intent ['.$client->getText().']');

            return $this->_httpFactory->buildResponse( $json);
        }
    }
}
