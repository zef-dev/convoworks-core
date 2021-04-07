<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\Publish\IPlatformPublisher;

class ServiceVersionsRestHandler implements RequestHandlerInterface
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
	 * @var \Convo\Core\Factory\ConvoServiceFactory
	 */
	private $_convoServiceFactory;

	/**
	 * @var \Convo\Core\IServiceDataProvider
	 */
	private $_convoServiceDataProvider;

	/**
	 * @var \Convo\Core\Publish\PlatformPublisherFactory
	 */
	private $_platformPublisherFactory;

	/**
	 * @var \Convo\Core\Publish\ServiceReleaseManager
	 */
	private $_serviceReleaseManager;

	public function __construct($logger, $httpFactory, $serviceFactory, $serviceDataProvider, $platformPublisherFactory, $serviceReleaseManager)
	{
		$this->_logger						= 	$logger;
		$this->_httpFactory					= 	$httpFactory;
		$this->_convoServiceFactory			= 	$serviceFactory;
		$this->_convoServiceDataProvider	= 	$serviceDataProvider;
		$this->_platformPublisherFactory	= 	$platformPublisherFactory;
		$this->_serviceReleaseManager       = 	$serviceReleaseManager;
	}

	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info	=	new \Convo\Core\Rest\RequestInfo( $request);

		$this->_logger->debug( 'Got info ['.$info.']');

		$user	=	$info->getAuthUser();

		if ( $info->get() && $route = $info->route( 'service-versions/{serviceId}'))
		{
		    return $this->_performServiceVersionsGet( $request, $user, $route->get( 'serviceId'));
		}

		if ( $info->post() && $route = $info->route('service-versions/{serviceId}/{versionId}'))
        {
            return $this->_performServiceVersionsPathServiceIdPathVersionIdPost( $request, $user, $route->get( 'serviceId'), $route->get( 'versionId'));
        }

		if ( $info->get() && $route = $info->route( 'service-releases/{serviceId}'))
		{
		    return $this->_performServiceReleasesGet( $request, $user, $route->get( 'serviceId'));
		}

		if ( $info->post() && $route = $info->route( 'service-releases/{serviceId}'))
		{
		    return $this->_performServiceReleasesPathServiceIdPathPlatformIdPathTypePost( $request, $user, $route->get( 'serviceId'));
		}

		if ( $info->put() && $route = $info->route( 'service-releases/{serviceId}'))
		{
		    return $this->_performServiceReleasesPathServiceIdPathPlatformIdPathTypePut( $request, $user, $route->get( 'serviceId'));
		}

		if ( $info->post() && $route = $info->route( 'service-releases/{serviceId}/{releaseId}/import-workflow/{versionId}'))
		{
		    return $this->_performServiceReleasesPathServiceIdPathReleaseIdPathImportWorkflowPathVersionIdPut(
		        $request, $user, $route->get( 'serviceId'), $route->get( 'releaseId'), $route->get( 'versionId'));
		}

        if ( $info->post() && $route = $info->route( 'service-releases/{serviceId}/import-develop/{versionId}'))
        {
            return $this->_performServiceReleasesPathServiceIdPathImportDevelopPathVersionIdPut(
                $request, $user, $route->get( 'serviceId'), $route->get( 'versionId'));
        }

		throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
	}

	private function _performServiceVersionsGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
	    $data = $this->_serviceReleaseManager->getAllServiceVersionsMeta( $user, $serviceId);

		$this->_logger->info('Getting all versions for ['.$serviceId.']');

		return $this->_httpFactory->buildResponse( $data);
	}

	private function _performServiceVersionsPathServiceIdPathVersionIdPost(
	    \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user,
        $serviceId, $versionId)
    {
        $json = $request->getParsedBody();

        $version_tag = $json['version_tag'] ?: null;

        $data = $this->_serviceReleaseManager->createSimpleVersionTag(
            $user, $serviceId, $versionId, $version_tag
        );

		$this->_logger->info('Getting version ['.$versionId.'] for ['.$serviceId.']');

        return $this->_httpFactory->buildResponse(['version_id' => $data]);
    }

	private function _performServiceReleasesPathServiceIdPathPlatformIdPathTypePost(
	\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId) {

	    $json         =   $request->getParsedBody();

		$this->_logger->info('Creating release for ['.$serviceId.']['.$json['platform_id'].']['.$json['type'].']['.$json['stage'].']');

	    $release       =   $this->_serviceReleaseManager->createServiceRelease(
	        $user, $serviceId, $json['platform_id'], $json['type'], $json['stage']);
	    return $this->_httpFactory->buildResponse( $release);
	}

	private function _performServiceReleasesPathServiceIdPathPlatformIdPathTypePut(
	\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId) {

	    $json         =   $request->getParsedBody();

		$this->_logger->info('Promoting release for ['.$serviceId.']['.$json['release_id'].']['.$json['type'].']['.$json['stage'].']');

	    $release       =   $this->_serviceReleaseManager->promoteRelease(
	        $user, $serviceId, $json['release_id'], $json['type'], $json['stage']);
	    return $this->_httpFactory->buildResponse( $release);
	}

	private function _performServiceReleasesPathServiceIdPathReleaseIdPathImportWorkflowPathVersionIdPut(
	\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user,
	    $serviceId, $releaseId, $versionId)
    {
        $release = $this->_serviceReleaseManager->importWorkflowIntoRelease(
            $user, $serviceId, $releaseId, $versionId);

		$this->_logger->info('Importing workflow ['.$serviceId.']['.$releaseId.']['.$versionId.']');

	    return $this->_httpFactory->buildResponse( $release);
	}

    private function _performServiceReleasesPathServiceIdPathImportDevelopPathVersionIdPut(
        \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user,
        $serviceId, $versionId)
    {
        $release = $this->_serviceReleaseManager->importWorkflowIntoDevelop(
            $user, $serviceId, $versionId
        );

		$this->_logger->info('Importing workflow from version ['.$serviceId.']['.$versionId.'] into development.');

        return $this->_httpFactory->buildResponse( $release);
    }

	private function _performServiceReleasesGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
		try {
			$meta = $this->_convoServiceDataProvider->getServiceMeta( $user, $serviceId);
		} catch (\Convo\Core\DataItemNotFoundException $e) {
			throw new \Convo\Core\Rest\NotFoundException( 'Service meta ['.$serviceId.'] not found', 0, $e);
		}

		$this->_logger->info('Getting all releases for ['.$serviceId.']');

	    $data  =   [];

	    if ( isset( $meta['release_mapping']))
	    {
	        foreach ( $meta['release_mapping'] as $platform_id => $platform_data)
	        {
	            foreach ( $platform_data as $alias => $mapping)
	            {
	                if ( $mapping['type'] === IPlatformPublisher::MAPPING_TYPE_RELEASE) {
	                    $release   =   $this->_convoServiceDataProvider->getReleaseData( $user, $serviceId, $mapping['release_id']);
	                } else {
	                    $workflow   =  $this->_convoServiceDataProvider->getServiceData( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	                    $release    =   [
	                        'service_id' => $serviceId,
	                        'release_id' => null,
	                        'type' => IPlatformPublisher::RELEASE_TYPE_DEVELOP,
	                        'version_id' => IPlatformPublisher::MAPPING_TYPE_DEVELOP,
	                        'platform_id' => $platform_id,
	                        'time_updated' => $workflow['time_updated'],
	                        'alias' => $alias
	                    ];
	                }

	                $data[]    =   $release;
	            }
	        }
	    }

	    usort( $data, [get_class( $this), 'compareReleases']);

		return $this->_httpFactory->buildResponse( $data);
	}


	public static function compareReleases( $a, $b) {
	    return strnatcmp( $a['release_id'] ?? '', $b['release_id'] ?? '');
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
