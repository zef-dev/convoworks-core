<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Convo\Core\Factory\PackageProviderFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TemplatesRestHandler implements \Psr\Http\Server\RequestHandlerInterface
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
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    public function __construct($logger, $httpFactory, $packageProviderFactory)
    {
        $this->_logger = $logger;
        $this->_httpFactory = $httpFactory;

        $this->_packageProviderFactory = $packageProviderFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $info = new \Convo\Core\Rest\RequestInfo($request);

        $this->_logger->debug('Got info ['.$info.']');

        $user = $info->getAuthUser();

        if ($info->get() && $route = $info->route('templates'))
        {
            return $this->_performTemplatesGet($request, $user);
        }

        throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
    }

    private function _performTemplatesGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user)
    {
        $template_sources = $this->_packageProviderFactory->getSourcesFor(PackageProviderFactory::SOURCE_TYPE_TEMPLATES);

        $data = [];

        foreach ($template_sources as $template_source)
        {
            $data = array_merge($data, $template_source->getRow()['templates']);
        }

        return $this->_httpFactory->buildResponse($data);
    }

    // UTIL

    public function __toString()
    {
        return get_class($this);
    }
}
