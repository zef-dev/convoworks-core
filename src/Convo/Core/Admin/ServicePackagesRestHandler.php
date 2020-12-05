<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Convo\Core\Rest\InvalidRequestException;
use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\Publish\IPlatformPublisher;

class ServicePackagesRestHandler implements RequestHandlerInterface
{
	/**
	 * @var \Convo\Core\Util\IHttpFactory
	 */
	private $_httpFactory;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
	private $_convoServiceDataProvider;

	/**
	 * @var \Convo\Core\Factory\PackageProviderFactory
	 */
	private $_packageProviderFactory;

	public function __construct($logger, $httpFactory, $convoServiceDataProvider, $packageProviderFactory)
	{
		$this->_logger = $logger;
		$this->_httpFactory = $httpFactory;
		$this->_convoServiceDataProvider = $convoServiceDataProvider;
		$this->_packageProviderFactory = $packageProviderFactory;
	}

	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info = new \Convo\Core\Rest\RequestInfo($request);

		$this->_logger->debug( 'Got info ['.$info.']');

		$user = $info->getAuthUser();

		if ($info->get() && $route = $info->route('service-packages/{serviceId}'))
		{
			return $this->_performServicePackagesPathServiceIdGet($request, $user, $route->get('serviceId'));
		}

		if ($info->post() && $route = $info->route('service-packages/{serviceId}'))
        {
            return $this->_performServicePackagesPathServiceIdPost($request, $user, $route->get('serviceId'));
        }

		if ($info->delete() && $route = $info->route('service-packages/{serviceId}'))
        {
            return $this->_performServicePackagesPathServiceIdDelete($request, $user, $route->get('serviceId'));
        }

		throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
	}

	private function _performServicePackagesPathServiceIdGet(\Psr\Http\Message\RequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
        $packages = $this->_getServicePackages($user, $serviceId);

		return $this->_httpFactory->buildResponse($packages);
	}

	private function _performServicePackagesPathServiceIdPost(\Psr\Http\Message\RequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
    {
        $body = json_decode($request->getBody()->__toString(), true);

        if (!isset($body['package_id'])) {
            throw new InvalidRequestException('Missing required property [package_id] in request body.');
        }

        $packages = $this->_addPackageToService($user, $serviceId, $body['package_id']);

        return $this->_httpFactory->buildResponse($packages);
    }

    private function _performServicePackagesPathServiceIdDelete(\Psr\Http\Message\RequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
    {
        $body = json_decode($request->getBody()->__toString(), true);

        if (!isset($body['package_id'])) {
            throw new InvalidRequestException('Missing required property [package_id] in request body.');
        }

        $packageId = $body['package_id'];

        $this->_logger->debug('Removing package ['.$packageId.'] from service ['.$serviceId.']');

        $packages = $this->_removePackageFromService($user, $serviceId, $packageId);

        return $this->_httpFactory->buildResponse($packages);
    }

	// UTIL
    private function _getServicePackages(\Convo\Core\IAdminUser $user, $serviceId)
    {
        $provider = $this->_packageProviderFactory->getProviderByServiceId($user, $serviceId);

        return $provider->getRow();
    }

    private function _addPackageToService($user, $serviceId, $packageId)
    {
        $service = $this->_convoServiceDataProvider->getServiceData(
            $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );

        array_push($service['packages'], $packageId);
        $this->_convoServiceDataProvider->saveServiceData($user, $serviceId, $service);

        return $this->_getServicePackages($user, $serviceId);
    }

    function _removePackageFromService($user, $serviceId, $packageId)
    {
        $service = $this->_convoServiceDataProvider->getServiceData(
            $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );

        $index = array_search($packageId, $service['packages']);

        if ($index === false) {
            throw new \Exception('Package ['.$packageId.'] not in service ['.$serviceId.']');
        }

        array_splice($service['packages'], $index, 1);
        $this->_convoServiceDataProvider->saveServiceData($user, $serviceId, $service);

        return $this->_getServicePackages($user, $serviceId);
    }

	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
