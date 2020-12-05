<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Rest\RestSystemUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CatalogRestHandler implements \Psr\Http\Server\RequestHandlerInterface
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
	 * @var \Convo\Core\IAdminUserDataProvider
	 */
	private $_adminUserDataProvider;

	/**
	 * @var \Convo\Core\Factory\ConvoServiceFactory
	 */
	private $_convoServiceFactory;

	/**
	 * @var \Convo\Core\IServiceDataProvider
	 */
	private $_convoServiceDataProvider;

	public function __construct($logger, $httpFactory, $adminUserDataProvider, $convoServiceFactory, $serviceDataProvider)
	{
		$this->_logger = $logger;
		$this->_httpFactory = $httpFactory;

		$this->_adminUserDataProvider = $adminUserDataProvider;

		$this->_convoServiceFactory = $convoServiceFactory;
		$this->_convoServiceDataProvider = $serviceDataProvider;
	}

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$info = new \Convo\Core\Rest\RequestInfo($request);

		$this->_logger->debug( 'Got info ['.$info.']');

		if ($info->get() && $route = $info->route('service-catalogs/{serviceId}/{catalogId}/{version}/{platform}')) {
			return $this->_performConvoPathServiceIdPathCatalogsPathCatalogIdGet(
				$request, $route->get('serviceId'), $route->get('catalogId'), $route->get('version'), $route->get('platform')
			);
		}

		throw new \Convo\Core\Rest\NotFoundException('Could not map ['.$info.']');
	}

	private function _performConvoPathServiceIdPathCatalogsPathCatalogIdGet(\Psr\Http\Message\ServerRequestInterface $request, $serviceId, $catalogId, $version, $platform)
	{
		$meta = $this->_convoServiceDataProvider->getServiceMeta(
			new RestSystemUser(),
			$serviceId
		);

		$owner = $meta['owner'] ?? null;

		if (!$owner) {
			throw new \Exception("Service [$serviceId] has no owner.");
		}

		$user = $this->_adminUserDataProvider->findUser($owner);
		$convo_version = $this->_convoServiceFactory->getVariantVersion(
			$user, $serviceId, $platform, $version
		);
		$instance = $this->_convoServiceFactory->getService(
			$user, $serviceId, $convo_version
		);

		/* @var \Convo\Core\Workflow\ICatalogSource $catalog */
		$catalog = $instance->findContext($catalogId)->getComponent();

		return $this->_httpFactory->buildResponse($catalog->getCatalogValues($platform));
	}

	// UTIL

	public function __toString()
	{
		return get_class($this).'[]';
	}
}
