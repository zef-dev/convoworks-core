<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Convo\Core\Rest\RequestInfo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ComponentHelpRestHandler implements \Psr\Http\Server\RequestHandlerInterface
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

    /**
     * @var \Convo\Core\IAdminUser
     */
    private $_user;

    public function __construct($logger, $httpFactory, $packageProviderFactory)
    {
        $this->_logger = $logger;
        $this->_httpFactory = $httpFactory;
        $this->_packageProviderFactory = $packageProviderFactory;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $info = new RequestInfo($request);

        $this->_user = $info->getAuthUser();

        if ($info->get() && $route = $info->route('package-help/{packageId}/{component}'))
        {
            return $this->_provideHtmlPackageComponentHelpFile($route->get('packageId'), $route->get('component'));
        }

        throw new \Convo\Core\Rest\NotFoundException('Could not map info ['.$info.']');
    }

    private function _provideHtmlPackageComponentHelpFile($packageId, $componentName) {
        $provider = $this->_packageProviderFactory->getProviderByNamespace($packageId);
        if ( !is_a( $provider, '\Convo\Core\Factory\IComponentProvider')) {
            throw new \Convo\Core\Rest\NotFoundException('Package is not component provider ['.$packageId.']');
        }

        /** @var \Convo\Core\Factory\IComponentProvider $provider */
        $help = [
            "html_content" => $provider->getComponentHelp($componentName)
        ];

        return $this->_httpFactory->buildResponse($help, 200, [
            'Content-Type' => 'application/json'
        ]);
    }
}
