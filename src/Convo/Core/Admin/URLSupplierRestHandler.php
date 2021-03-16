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
    private $_URLSupplier;

    public function __construct($logger, $httpFactory, $URLSupplier)
    {
        $this->_logger		= $logger;
        $this->_httpFactory = $httpFactory;
        $this->_URLSupplier = $URLSupplier;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $info			=	new \Convo\Core\Rest\RequestInfo( $request);

        if ($info->get() && $route = $info->route( 'supply-urls/system-urls')) {
            $data = $this->_URLSupplier->getSystemUrls();

            return $this->_httpFactory->buildResponse($data);
        }

        if ($info->get() && $route = $info->route( 'supply-urls/service-urls/{serviceId}')) {
            $serviceId = $route->get('serviceId');
            $data = $this->_URLSupplier->getServiceUrls($serviceId);

            return $this->_httpFactory->buildResponse($data);
        }

        throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
    }
}
