<?php declare(strict_types=1);

namespace Convo\Core\Factory;

use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Intent\IntentModel;
use Convo\Core\Intent\EntityModel;

class ConvoServiceFactory
{
	const SERVICE_VERSION_ATTRIBUTE		=	'convo_service_version';
	const SERVICE_VERSION	=	39;

	/**
	 * @var \Convo\Core\Factory\PackageProviderFactory
	 */
	private $_packageProviderFactory;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	/**
	 * @var \Convo\Core\IServiceDataProvider
	 */
	private $_convoServiceDataProvider;

	private $_serviceUsersDao;

	public function __construct(
	    \Psr\Log\LoggerInterface $logger,
	    \Convo\Core\Factory\PackageProviderFactory $packageProviderFactory,
        \Convo\Core\IServiceDataProvider $convoServiceDataProvider
    )
	{
		$this->_logger							=	$logger;
		$this->_packageProviderFactory			=	$packageProviderFactory;
		$this->_convoServiceDataProvider		=	$convoServiceDataProvider;
	}

	/**
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $versionId
	 * @return \Convo\Core\ConvoServiceInstance
	 */
	public function getService(\Convo\Core\IAdminUser $user, $serviceId, $versionId, $convoServiceParamsFactory)
	{
		$this->_logger->info( 'Creating service ['.$serviceId.']['.$versionId.']');

		$data		=	$this->_convoServiceDataProvider->getServiceData( $user, $serviceId, $versionId);
		$this->_logger->debug( 'Data loaded');

        $provider = $this->_packageProviderFactory->getProviderFromPackageIds($data['packages']);
        $eval = new \Convo\Core\Expression\EvaluationContext($this->_logger, $provider);

		$service	=	new \Convo\Core\ConvoServiceInstance(
			$this->_logger,
			$eval,
			$convoServiceParamsFactory,
			$user,
			$serviceId
		);
		$service->setVariables( $data['variables']);
		$service->setPreviewVariables( $data['preview_variables']);
		$service->setPackageIds($data['packages']);

		// INTENTS
		if ( isset( $data['intents'])) {
		    foreach ( $data['intents'] as $intent_data) {
		        $intent    =   new IntentModel();
		        $intent->load( $intent_data);
		        $service->addIntent( $intent);
		    }
		}

	    // ENTITIES
	    if ( isset( $data['entities'])) {
	        foreach ( $data['entities'] as $entity_data) {
	            $entity    =   new EntityModel();
	            $entity->load( $entity_data);
	            $service->addEntity( $entity);
	        }
	    }

// 		foreach ( $data['configurations'] as $configuration) {
// 			$service->addConfig( $this->_packageProvider->createComponent( $service, $configuration));
// 		}

		foreach ( $data['contexts'] as $context) {
			/** @var \Convo\Core\Workflow\IServiceContext $context */
			$service->addEvalContext( $provider->createComponent( $service, $context));
		}

		foreach ( $data['blocks'] as $block)
		{
			$service->addBlock( $provider->createComponent( $service, $block));
		}

		foreach ( $data['fragments'] as $fragment)
		{
			$service->addFragments( $provider->createComponent( $service, $fragment));
		}

		return $service;
	}

	public function getVariantVersion( \Convo\Core\IAdminUser $user, $serviceId, $platformId, $variant)
	{
	    if ( $variant === IPlatformPublisher::RELEASE_TYPE_DEVELOP) {
			return $variant;
		}

		$meta	=	$this->_convoServiceDataProvider->getServiceMeta( $user, $serviceId);

		if ( !isset( $meta['release_mapping'][$platformId])) {
			throw new \Convo\Core\ComponentNotFoundException( 'No release definition for service ['.$serviceId.'] platform ['.$platformId.']');
		}

		$platform_data =   $meta['release_mapping'][$platformId];

		if ( !isset( $platform_data[$variant])) {
			throw new \Convo\Core\ComponentNotFoundException( 'No release definition for service ['.$serviceId.'] platform ['.$platformId.'] variant ['.$variant.']');
		}

		if ( $platform_data[$variant]['type'] === IPlatformPublisher::MAPPING_TYPE_DEVELOP) {
		    return IPlatformPublisher::MAPPING_TYPE_DEVELOP;
		}

		if ( $platform_data[$variant]['type'] === IPlatformPublisher::MAPPING_TYPE_RELEASE) {
		    $release  =   $this->_convoServiceDataProvider->getReleaseData( $user, $serviceId, $platform_data[$variant]['release_id']);
		    return $release['version_id'];
		}

		throw new \Exception( 'Not expected type ['.$platform_data[$variant]['type'].']');
	}

	private function _getServiceVersion( $servivceData) {
		if ( !isset( $servivceData[ConvoServiceFactory::SERVICE_VERSION_ATTRIBUTE])) {
			return 0;
		}
		if ( is_int( $servivceData[ConvoServiceFactory::SERVICE_VERSION_ATTRIBUTE])) {
			return $servivceData[ConvoServiceFactory::SERVICE_VERSION_ATTRIBUTE];
		}

		throw new \Exception( 'Invalid service version ['.$servivceData[ConvoServiceFactory::SERVICE_VERSION_ATTRIBUTE].']');
	}


	/**
	 * Will traverze through service data and set _component_id if it is missing on particular component.
	 * @param array $serviceData
	 */
	public function fixComponentIds( &$serviceData)
	{
	    array_walk( $serviceData, function ( &$item) {
	        if ( is_array( $item) && isset( $item['class']) &&
	            (!isset( $item['properties']['_component_id']) || empty( $item['properties']['_component_id']))) {
	            $new_id    =   self::generateId();
	            $this->_logger->debug( 'Setting missing _component_id ['.$new_id.']');
	            $item['properties']['_component_id']  =   $new_id;
	        }

	        if ( is_array( $item)) {
	            $this->fixComponentIds( $item);
	        }
	    });
	}

	/**
	 * Generates component id
	 * @return string
	 */
	public static function generateId()
	{
	    return strtolower( bin2hex( random_bytes( 4))).'-'.
	   	    strtolower( bin2hex( random_bytes( 2))).'-'.
	   	    strtolower( bin2hex( random_bytes( 2))).'-'.
	   	    strtolower( bin2hex( random_bytes( 2))).'-'.
	   	    strtolower( bin2hex( random_bytes( 6)));
	}

	public function migrateService( \Convo\Core\IAdminUser $user, $serviceId, \Convo\Core\IServiceDataProvider $provider) {
	    $data		=	$provider->getServiceData( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	    $config		=	$provider->getServicePlatformConfig( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	    $meta		=	$provider->getServiceMeta( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

		$version	=	$this->_getServiceVersion( $data);

		if ( $version < self::SERVICE_VERSION) {

			$this->_logger->debug( 'Migrating service from ['.$version.'] to ['.self::SERVICE_VERSION.']');

			$migrations	=	$this->_getMigrationsFrom( $version);

			foreach ( $migrations as $migration) {
				$migration->setLogger( $this->_logger);
				$this->_logger->debug( 'Migrating with ['.$migration.']');
				$data	=	$migration->migrate( $data);
				$config	=	$migration->migrateConfig( $config);
				$meta	=	$migration->migrateMeta( $meta);
			}

			$this->_logger->debug( 'Saving migrated service');
			$provider->saveServiceData( $user, $serviceId, $data);
			$provider->updateServicePlatformConfig( $user, $serviceId, $config);
			$provider->saveServiceMeta( $user, $serviceId, $meta);
		}
	}

	/**
	 * @param int $version
	 * @return \Convo\Core\Migrate\AbstractMigration
	 */
	private function _getMigrationsFrom( $version) {
		$migrations	=	[];
		$all		=	$this->_getAllMigrations();

		foreach ( $all as $migration) {
			if ( $migration->getVersion() > $version && $migration->getVersion() <= self::SERVICE_VERSION) {
				$migrations[]	=	$migration;
			}
		}

		return $migrations;
	}

	/**
	 * @return \Convo\Core\Migrate\AbstractMigration[]
	 */
	private function _getAllMigrations()
	{
		$migrations		=	[];

		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo1();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo2();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo3();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo4();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo5();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo6();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo7();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo8();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo9();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo10();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo11();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo12();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo13();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo14();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo16();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo17();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo18();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo19();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo20();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo21();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo22();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo23();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo24();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo25();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo26();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo27();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo28();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo29();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo30();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo31();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo32();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo33();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo34();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo35();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo36();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo37();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo38();
		$migrations[]	=	new \Convo\Core\Migrate\MigrateTo39();

		return $migrations;
	}


	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
