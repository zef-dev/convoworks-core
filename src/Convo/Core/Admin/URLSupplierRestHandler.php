<?php


namespace Convo\Core\Admin;


use Convo\Core\IURLSupplier;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class URLSupplierRestHandler implements RequestHandlerInterface
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
     * @var IURLSupplier
     */
    private $_serviceURLSupplier;

    public function __construct($logger, $httpFactory, $serviceURLSupplier)
    {
        $this->_logger				= 	$logger;
        $this->_httpFactory			= 	$httpFactory;
        $this->_serviceURLSupplier  = 	$serviceURLSupplier;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $info			=	new \Convo\Core\Rest\RequestInfo( $request);

        // todo rather load all system urls instead of giving reason
        // json for system and service level
        if ($info->get() && $route = $info->route( 'supply-urls/system-url/{forWhat}')) {
            $forWhat = $route->get('forWhat');
            $data = $this->_serviceURLSupplier->getSystemUrl($forWhat);

            return $this->_httpFactory->buildResponse($data);
        }

        if ($info->get() && $route = $info->route( 'supply-urls/service-url/{serviceId}/{platformId}/{forWhat}/{accountLinkingMode}')) {
            $serviceId = $route->get('serviceId');
            $platformId = $route->get('platformId');
            $forWhat = $route->get('forWhat');
            $accountLinkingMode = $route->get('accountLinkingMode');
            $data = $this->_serviceURLSupplier->getServiceUrl($serviceId, $platformId, $forWhat, $accountLinkingMode);

            return $this->_httpFactory->buildResponse($data);
        }

        throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
    }
}
