<?php declare(strict_types=1);

namespace Convo\Core;


/**
 * @author Tole
 *
 * Service data provider defines methods to read and update all service related data.
 *
 * Convoworks service data can be grouped int three main parts: workflow definition, meta information and version information.
 * Meta information describes service ownership and current release status.
 * Workflow describes service blocks and components - conversation definition.
 * Version and release information are to enable ability to have several versions of the workflow. e.g. one is production, one is new development
 *
 * Service data is an associaive array.
 * You can notice that there is no DEFAULT definition for configurations. That is because we can not know what parameters platform configuration has.
 * 
 * This interface allows you to store service data how it is the most appropriate for your application.
 */
interface IServiceDataProvider
{

    const DEFAULT_WORKFLOW	=	[
        'service_id' => null,
        'convo_service_version' => \Convo\Core\Factory\ConvoServiceFactory::SERVICE_VERSION,
        'packages' => ['convo-core'],
        'contexts' => [],
        'variables' => [],
        'preview_variables' => [],
        'entities' => [],
        'intents' => [],
        'blocks' => [],
        'fragments' => [],
        'time_updated' => 0,
        'intents_time_updated' => 0,
    ];

    const DEFAULT_META	=	[
        'service_id' => null,
        'name' => null,
        'description' => null,
        'default_language' => IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH,
        'default_locale' => IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US,
        'supported_locales' => [IConvoServiceLanguageMapper::CONVO_SERVICE_ENGLISH_US],
        'active' => 0,
        'is_private' => false,
        'owner' => null,
        'admins' => [],
        'release_mapping' => [],
        'time_updated' => 0,
    ];

    const DEFAULT_RELEASE	=	[
        'service_id' => null,
        'release_id' => null,
        'platform_id' => null,
        'version_id' => null,
        'type' => null,
        'stage' => null,
        'alias' => null,
        'time_created' => 0,
        'time_updated' => 0,
    ];


    /**
     * Returns all services which are visible to the given admin user. Returned services are represented as service meta definition.
     * @param \Convo\Core\IAdminUser $user
     * @return array
     */
    public function getAllServices( \Convo\Core\IAdminUser $user);

    /**
     * Creates service with the initial workflow data. Creating user will be set as owner.
     * @param \Convo\Core\IAdminUser $user
     * @param string $serviceName Name for the service.
     * @param string $defaultLanguage Default language of the service.
     * @param string $defaultLocale
     * @param string[] $supportedLocales
     * @param bool $isPrivate Non private services are accessible by all admin users.
     * @param string[] $serviceAdmins array of user emails which should be able to access service even if it is private.
     * @param array $workflowData
     * @return string new service_id
     */
    public function createNewService( \Convo\Core\IAdminUser $user, $serviceName, $defaultLanguage, $defaultLocale, $supportedLocales, $isPrivate, $serviceAdmins, $workflowData);


    /**
     * Removes all service data (workflow data, meta, versions)
     * @param \Convo\Core\IAdminUser $user
     * @param string $serviceId
     */
    public function deleteService( \Convo\Core\IAdminUser $user, $serviceId);

	/**
	 * Returns service workflow data  as associative array.
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $versionId might be version id or IPlatformPublisher::MAPPING_TYPE_DEVELOP
	 * @throws \Convo\Core\DataItemNotFoundException
	 * @throws \Convo\Core\Rest\NotAuthorizedException
	 * @return array
	 */
	public function getServiceData( \Convo\Core\IAdminUser $user, $serviceId, $versionId);


	/**
	 * Saves service worfklow data.
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param array $workflowData
	 * @throws \Convo\Core\DataItemNotFoundException
	 * @throws \Convo\Core\Rest\NotAuthorizedException
	 * @return array
	 */
	public function saveServiceData( \Convo\Core\IAdminUser $user, $serviceId, $workflowData);



	/**
	 * Returns service meta. If $versionId is ommited, should return development version meta, is specified, should return version meta.
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $versionId might be version id or IPlatformPublisher::MAPPING_TYPE_DEVELOP
	 * @return array
	 */
	public function getServiceMeta( \Convo\Core\IAdminUser $user, $serviceId, $versionId=null);

	/**
	 * Saves service meta information. Like getServiceMeta(), it can relate to current or tagged version data.
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param array $meta
	 * @throws \Convo\Core\DataItemNotFoundException
	 * @return array
	 */
	public function saveServiceMeta( \Convo\Core\IAdminUser $user, $serviceId, $meta);

	/**
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $versionId
	 * @param string $releaseId
	 */
	public function markVersionAsRelease( \Convo\Core\IAdminUser $user, $serviceId, $versionId, $releaseId);


	/**
	 * Returns all tagged versions, a array of string version_id.
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @return array
	 */
	public function getAllServiceVersions( \Convo\Core\IAdminUser $user, $serviceId);


	/**
	 * Tags current service workflow and configuration under the new tag (returned string value)
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param array $workflow Complete service workflow data
	 * @param array $config Complete service config data
	 * @param string $versionTag Optional custom tag name
	 * @return string Newly created version tag
	 */
	public function createServiceVersion( \Convo\Core\IAdminUser $user, $serviceId, $workflow, $config, $versionTag=null);


	/**
	 * Creates release from given service version.
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $platformId
	 * @param string $type
	 * @param string $stage
	 * @param string $alias
	 * @param string $versionId
	 * @param array $meta
	 * @return string Newly created release tag
	 */
	public function createRelease(\Convo\Core\IAdminUser $user, $serviceId, $platformId, $type, $stage, $alias, $versionId, $meta);


	/**
	 * Returns release meta data.
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $releaseId
	 * @throws \Convo\Core\DataItemNotFoundException
	 * @return array
	 */
	public function getReleaseData( \Convo\Core\IAdminUser $user, $serviceId, $releaseId);

	/**
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $releaseId
	 * @param string $type
	 * @param string $stage
	 */
	public function promoteRelease( \Convo\Core\IAdminUser $user, $serviceId, $releaseId, $type, $stage);

	/**
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $releaseId
	 * @param string $versionId
	 * @param array $meta
	 */
	public function setReleaseVersion(\Convo\Core\IAdminUser $user, $serviceId, $releaseId, $versionId, $meta);


	/**
	 * Returns service configuration for particular platform. Throws an exception if not exists.
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $versionId might be version id or IPlatformPublisher::MAPPING_TYPE_DEVELOP
	 * @throws \Convo\Core\DataItemNotFoundException
	 * @return array
	 */
	public function getServicePlatformConfig( \Convo\Core\IAdminUser $user, $serviceId, $versionId);

	/**
	 * Updates (or creates if not exists) service platofrm configuration.
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param array $config
	 */
	public function updateServicePlatformConfig( \Convo\Core\IAdminUser $user, $serviceId, $config);
}
