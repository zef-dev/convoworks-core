<?php declare(strict_types=1);

namespace Convo\Core\Publish;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\IAdminUser;

class ServiceReleaseManager
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    /**
     * @var string
     */
    private $_publicRestBaseUrl;

    public function __construct(
        $logger,
        $serviceDataProvider,
        $publicRestBaseUrl
    )
    {
        $this->_logger = $logger;
        $this->_convoServiceDataProvider = $serviceDataProvider;
        $this->_publicRestBaseUrl = $publicRestBaseUrl;
    }

    public function createServiceRelease(IAdminUser $user, $serviceId, $platformId, $type, $stage)
    {
        $alias = $this->getDevelopmentAlias($user, $serviceId, $platformId);
        $meta = $this->_convoServiceDataProvider->getServiceMeta($user, $serviceId);
        // tag version
        $data = $this->_convoServiceDataProvider->getServiceData($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        $version_id = $this->_convoServiceDataProvider->createServiceVersion($user, $serviceId, $data, $config);
        $new_release_id = $this->_convoServiceDataProvider->createRelease($user, $serviceId, $platformId, $type, $stage, $alias, $version_id, $meta);

        $this->_convoServiceDataProvider->markVersionAsRelease( $user, $serviceId, $version_id, $new_release_id);

        // if slot taken - discard all release
        $release = $this->_findReleaseInMeta($user, $serviceId, $platformId, $type, $stage);
        if ($release) {
            $this->withdrawRelease($user, $serviceId, $release['release_id']);
        }
        $meta = $this->_setPlatformRelease($user, $serviceId, $platformId, $new_release_id);

        // set new alias to develop
        $meta = $this->initDevelopmentRelease($user, $serviceId, $platformId);

        return $meta;
    }

    public function promoteRelease(IAdminUser $user, $serviceId, $releaseId, $type, $stage)
    {
        $release = $this->_convoServiceDataProvider->getReleaseData($user, $serviceId, $releaseId);

        $old = $this->_findReleaseInMeta($user, $serviceId, $release['platform_id'], $type, $stage);
        if ($old) {
            $this->withdrawRelease($user, $serviceId, $old['release_id']);
        }

        $this->_convoServiceDataProvider->promoteRelease( $user, $serviceId, $releaseId, $type, $stage);

        $meta = $this->_setPlatformRelease($user, $serviceId, $release['platform_id'], $releaseId);
        return $meta;
    }

    public function withdrawRelease(IAdminUser $user, $serviceId, $releaseId)
    {
        $meta = $this->_convoServiceDataProvider->getServiceMeta($user, $serviceId);
        $release = $this->_convoServiceDataProvider->getReleaseData($user, $serviceId, $releaseId);

        foreach ($meta['release_mapping'][$release['platform_id']] as $alias => $mapping) {
            if ($mapping['type'] === IPlatformPublisher::MAPPING_TYPE_RELEASE && $mapping['release_id'] === $releaseId) {
                unset($meta['release_mapping'][$release['platform_id']][$alias]);
            }
        }

        $meta = $this->_convoServiceDataProvider->saveServiceMeta($user, $serviceId, $meta);
        return $meta;
    }

    public function importWorkflowIntoRelease(IAdminUser $user, $serviceId, $releaseId, $versionId)
    {
        $workflow = $this->_convoServiceDataProvider->getServiceData($user, $serviceId, $versionId);
        $release = $this->_convoServiceDataProvider->getReleaseData($user, $serviceId, $releaseId);
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, $release['version_id']);
        $meta = $this->_convoServiceDataProvider->getServiceMeta($user, $serviceId);
        $version_id = $this->_convoServiceDataProvider->createServiceVersion($user, $serviceId, $workflow, $config);
        $this->_convoServiceDataProvider->markVersionAsRelease( $user, $serviceId, $version_id, $releaseId);
        $this->_convoServiceDataProvider->setReleaseVersion( $user, $serviceId, $releaseId, $version_id, $meta);
        return $this->_convoServiceDataProvider->getReleaseData( $user, $serviceId, $releaseId);
    }

    public function importWorkflowIntoDevelop(IAdminUser $user, $serviceId, $versionId)
    {
        // Get specific version flow
        $workflow = $this->_convoServiceDataProvider->getServiceData($user, $serviceId, $versionId);
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, $versionId);

        $release_data = [];
        try {
            $release_data = $this->_convoServiceDataProvider->getReleaseData($user, $serviceId, $versionId);
        } catch (DataItemNotFoundException $e) {
            $this->_logger->warning($e->getMessage());
        }

        $meta = $this->_convoServiceDataProvider->getServiceMeta($user, $serviceId);
        $meta['default_language'] = $release_data['default_language'] ?? 'en';
        $meta['default_locale'] = $release_data['default_locale'] ?? 'en-US';
        $meta['supported_locales'] = $release_data['supported_locales'] ?? ['en-US'];
        $meta['time_updated'] = time();

        // Save flow as develop
        $this->_convoServiceDataProvider->saveServiceData($user, $serviceId, $workflow);
        $this->_convoServiceDataProvider->updateServicePlatformConfig($user, $serviceId, $config);
        $this->_convoServiceDataProvider->saveServiceMeta($user, $serviceId, $meta);

        return $this->_convoServiceDataProvider->getServiceData($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
    }

    public function createSimpleVersionTag(IAdminUser $user, $serviceId, $versionId, $versionTag = null)
    {
        $workflow = $this->_convoServiceDataProvider->getServiceData($user, $serviceId, $versionId);
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, $versionId);

        return $this->_convoServiceDataProvider->createServiceVersion($user, $serviceId, $workflow, $config, $versionTag);
    }

    public function getAllServiceVersionsMeta( IAdminUser $user, $serviceId)
    {
        $versions   =   $this->_convoServiceDataProvider->getAllServiceVersions( $user, $serviceId);
        $this->_logger->debug( 'Found ['.count( $versions).']');

        $all       =    [];
        $meta      =    $this->_convoServiceDataProvider->getServiceMeta( $user, $serviceId);

        foreach ( $versions as $version_id)
        {
            $this->_logger->debug( 'Handling version ['.$version_id.']');

            $version_meta    =  $this->_convoServiceDataProvider->getServiceMeta( $user, $serviceId, $version_id);

            if ( $version_meta['release_id']) {
                $release         =  $this->_convoServiceDataProvider->getReleaseData( $user, $serviceId, $version_meta['release_id']);
            } else {
                $release         =   [];
            }

            $row		=	[
                'version_id' => $version_id,
                'platform_id' => $release['platform_id'] ?? null,
                'alias' => $release['alias'] ?? null,
                'type' => $release['type'] ?? null,
                'stage' => $release['stage'] ?? null,
                'active' => false,
                'release_id' => $version_meta['release_id'],
                'version_tag' => $version_meta['version_tag'] ?? '',
                'time_created' => $version_meta['time_created'] ?? 0,
            ];

            foreach ( $meta['release_mapping'] as $platform_id => $platform_data) {
                foreach ( $platform_data as $alias => $mapping) {
                    if ( $mapping['type'] === IPlatformPublisher::MAPPING_TYPE_DEVELOP) {
                        continue;
                    }
                    $release             =   $this->_convoServiceDataProvider->getReleaseData( $user, $serviceId, $mapping['release_id']);

                    if ( $release['version_id'] !== $version_id) {
                        continue;
                    }

                    $this->_logger->debug( 'Found mapping in ['.$serviceId.']['.$platform_id.']['.$alias.']');

                    $row['platform_id']  =    $release['platform_id'];
                    $row['alias']        =    $release['alias'];
                    $row['type']         =    $release['type'];
                    $row['stage']        =    $release['stage'];
                    $row['active']       =    true;
                }
            }

            $all[]       =   $row;
        }

        usort( $all, [get_class( $this), 'compareVersions']);
        return array_slice( $all, 0, 20);
    }

    public static function compareVersions( $a, $b) {
        return strnatcmp( $a['version_id'], $b['version_id']) * -1;
    }

	/**
	 * @param IAdminUser $user
	 * @param string $serviceId
	 * @param string $platformId
	 * @throws \Exception
	 * @return array
	 */
	public function initDevelopmentRelease( IAdminUser $user, $serviceId, $platformId, $alias=null)
	{
	    $meta           =   $this->_convoServiceDataProvider->getServiceMeta( $user, $serviceId);

	    if ( is_null( $alias))
	    {
	        $ALL   =   [ 'a' => true, 'b' => true, 'c' => true, 'd' => true];
	        if ( isset( $meta['release_mapping'][$platformId])) {
	            foreach ( $meta['release_mapping'][$platformId] as $alias=>$mapping) {
	                if ( $mapping['type'] === IPlatformPublisher::MAPPING_TYPE_RELEASE) {
	                    if ( isset( $ALL[$alias])) {
	                        unset( $ALL[$alias]);
	                    }
	                }
	            }
	        }

	        if ( empty( $ALL)) {
	            throw new \Exception( 'No more aliases when initializing develop for ['.$serviceId.']['.$platformId.']');
	        }

	        $keys  =   array_keys( $ALL);
	        $alias =   array_shift( $keys);
	    }

	    $meta['release_mapping'][$platformId][$alias]   =   [
	        'type' => IPlatformPublisher::MAPPING_TYPE_DEVELOP,
	        'time_updated' => time(),
	        'time_propagated' => 0
	    ];

	    $meta  =   $this->_convoServiceDataProvider->saveServiceMeta( $user, $serviceId, $meta);

	    return $meta;
	}

	public function getWebhookUrl( IAdminUser $user, $serviceId, $platformId) {
	    $alias =   $this->getDevelopmentAlias( $user, $serviceId, $platformId);
	    return $this->_publicRestBaseUrl."/service-run/$platformId/$alias/$serviceId";

	}

	public function getDevelopmentAlias( IAdminUser $user, $serviceId, $platformId)
	{
	    $meta           =   $this->_convoServiceDataProvider->getServiceMeta( $user, $serviceId);

	    if ( !isset( $meta['release_mapping'][$platformId])) {
	        throw new \Exception( 'No release mapping defined for ['.$serviceId.']['.$platformId.']');
	    }

	    foreach ( $meta['release_mapping'][$platformId] as $alias=>$mapping) {
	        if ( $mapping['type'] === IPlatformPublisher::MAPPING_TYPE_DEVELOP) {
	            return $alias;
	        }
	    }

	    throw new \Exception( 'No alias defined for develop in ['.$serviceId.']['.$platformId.']');
	}

	/**
	 * @param IAdminUser $user
	 * @param string $serviceId
	 * @param string $platformId
	 * @param string $releaseId
	 * @return array
	 */
	private function _setPlatformRelease( IAdminUser $user, $serviceId, $platformId, $releaseId)
	{
	    $meta           =   $this->_convoServiceDataProvider->getServiceMeta( $user, $serviceId);
	    $release        =   $this->_convoServiceDataProvider->getReleaseData($user, $serviceId, $releaseId);
	    $meta['release_mapping'][$platformId][$release['alias']]   =   [
	        'type' => IPlatformPublisher::MAPPING_TYPE_RELEASE,
	        'release_id' => $releaseId,
	        'time_updated' => time()
	    ];
	    $meta  =   $this->_convoServiceDataProvider->saveServiceMeta( $user, $serviceId, $meta);
	    return $meta;
	}

	private function _findReleaseInMeta( $user, $serviceId, $platformId, $type, $stage) {
	    $meta           =   $this->_convoServiceDataProvider->getServiceMeta( $user, $serviceId);
	    foreach ( $this->_getMetaReleaseIds( $meta, $platformId) as $release_id) {
	        $release   =   $this->_convoServiceDataProvider->getReleaseData( $user, $serviceId, $release_id);
	        if ( $release['type'] === $type && $release['stage'] === $stage) {
	            return $release;
	        }
	    }
	    return null;
   	}

	private function _getMetaReleaseIds( $meta, $platformId) {
	    $ids   =   [];

	    foreach ( $meta['release_mapping'] as $platform_id=>$platform_data)
	    {
	        if ( $platform_id !== $platformId) {
	            continue;
	        }

	        foreach ( $platform_data as $mapping) {
	            if ( $mapping['type'] === IPlatformPublisher::MAPPING_TYPE_RELEASE) {
	                $ids[] = $mapping['release_id'];
	            }
	        }
	    }

	    return $ids;
	}


	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
