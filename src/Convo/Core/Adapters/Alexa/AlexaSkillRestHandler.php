<?php

declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Adapters\Alexa\Validators\AlexaRequestValidator;
use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\Rest\RestSystemUser;

class AlexaSkillRestHandler implements RequestHandlerInterface
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
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /*
     * @var \Convo\Core\Adapters\Alexa\Validators\AlexaRequestValidator
     */
    private $_alexaRequestValidator;

    public function __construct( \Psr\Log\LoggerInterface $logger, $httpFactory, $serviceFactory, $serviceDataProvider, $serviceParamsFactory, AlexaRequestValidator $alexaRequestValidator)
    {
    	$this->_logger						=	$logger;
    	$this->_httpFactory					=	$httpFactory;
        $this->_convoServiceFactory 		=	$serviceFactory;
        $this->_convoServiceDataProvider	=	$serviceDataProvider;
        $this->_convoServiceParamsFactory	=	$serviceParamsFactory;
        $this->_alexaRequestValidator       =   $alexaRequestValidator;
    }

    public function handle( \Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
    	$info	=	new \Convo\Core\Rest\RequestInfo( $request);

    	$this->_logger->debug( 'Got info ['.$info.']');

        if ( $info->post() && $route = $info->route('service-run/alexa-skill/{variant}/{serviceId}'))
        {
            $variant = $route->get('variant');
            $serviceId = $route->get('serviceId');
            $this->_logger->warning( 'DEPRECATION [alexa-skill] is deprecated in path use [amazon] in ['.$info.']['.$route.']');
        }
        else if ( $info->post() && $route = $info->route('service-run/amazon/{variant}/{serviceId}'))
        {
            $variant = $route->get('variant');
            $serviceId = $route->get('serviceId');
        }
        else
        {
            throw new \Convo\Core\Rest\NotFoundException('Could not map ['.$info.']');
        }

        $servicePlatformConfig = $this->_convoServiceDataProvider->getServicePlatformConfig(
            new RestSystemUser(),
            $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        $validationResult = $this->_alexaRequestValidator->verifyRequest($request, $servicePlatformConfig);
        if ($validationResult["verifiedSkillId"] === false ||
            $validationResult["verifiedRequestTimestamp"] === false ||
            $validationResult["validCertificateUrl"] === false ||
            $validationResult["verifiedCertificate"] === false) {
            return $this->_httpFactory->buildResponse(["errorMessage" => "Validation of amazon request failed."], 400);
        }
        return $this->_handleAlexaSkillPathServiceIdPost($request, $variant, $serviceId, $servicePlatformConfig);
    }

    private function _handleAlexaSkillPathServiceIdPost(\Psr\Http\Message\ServerRequestInterface $request, $variant, $serviceId, $servicePlatformConfig)
    {
    	$owner		=	new RestSystemUser();

        try {
            $version_id			=	$this->_convoServiceFactory->getVariantVersion( $owner, $serviceId, AmazonCommandRequest::PLATFORM_ID, $variant);
        } catch ( \Convo\Core\ComponentNotFoundException $e) {
            throw new \Convo\Core\Rest\NotFoundException( 'Service variant ['.$serviceId.']['.$variant.'] not found', 0, $e);
        }

    	$service 	=	$this->_convoServiceFactory->getService( $owner, $serviceId, $version_id, $this->_convoServiceParamsFactory);

        $this->_logger->info("Running variant [$variant] of [$serviceId]");

        $text_request = new \Convo\Core\Adapters\Alexa\AmazonCommandRequest( $this->_logger, $serviceId, $request->getParsedBody());
        $text_request->init();

        $this->_logger->info('Got request [' . $text_request . ']');
        $text_response = new \Convo\Core\Adapters\Alexa\AmazonCommandResponse($text_request);
        $text_response->setLogger($this->_logger);

        $service->run($text_request, $text_response);

        $this->_logger->info('Got response [' . $text_response . ']');

        $text_response->setIsDisplaySupported($text_request->getIsDisplaySupported());
        $text_response->setServiceAmazonConfig($servicePlatformConfig['amazon']);

        $data = $text_response->getPlatformResponse();

        $this->_logger->info('Got amazon response [' . json_encode($data) . ']');

        $this->_logger->info('Checking request ['.$text_request->getIntentName().']['.$text_request->getIntentType().']');

        if ( $text_request->getIntentType() === 'SessionEndedRequest') {
            $this->_logger->info('Building empty response for SessionEndedRequest');
            
            $text_response->prepareResponse(IAlexaResponseType::EMPTY_RESPONSE);
            $emptySessionEndResponse = $text_response->getPlatformResponse();
            
            return $this->_httpFactory->buildResponse($emptySessionEndResponse);
        }

        return $this->_httpFactory->buildResponse($data);
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
