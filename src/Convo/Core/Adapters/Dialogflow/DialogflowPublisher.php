<?php

declare(strict_types=1);

namespace Convo\Core\Adapters\Dialogflow;

use Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandRequest;
use Convo\Core\Intent\IIntentDriven;
use Convo\Core\Util\SimpleFileResource;
use Convo\Core\Util\StrUtil;
use Convo\Core\Util\ZipFileResource;
use Google\Cloud\Dialogflow\V2\Agent;
use Google\Cloud\Dialogflow\V2\Agent\ApiVersion;
use Google\Cloud\Dialogflow\V2\Agent\MatchMode;
use Google\Cloud\Dialogflow\V2\Agent\Tier;
use Convo\Core\ComponentNotFoundException;
use Convo\Core\Publish\IPlatformPublisher;

class DialogflowPublisher extends \Convo\Core\Publish\AbstractServicePublisher
{

    /**
     * @var \Convo\Core\Factory\ConvoServiceFactory
     */
    private $_convoServiceFactory;

    /**
     * @var \Convo\Core\Params\IServiceParamsFactory
     */
    private $_convoServiceParamsFactory;

    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    /**
     * @var \Convo\Core\Adapters\Dialogflow\DialogflowApiFactory
     */
    private $_dialogflowApiFactory;

    /**
     * @var \Convo\Core\Media\IServiceMediaManager
     */
    private $_mediaService;

    public function __construct(
        $logger,
        \Convo\Core\IAdminUser $user,
        $serviceId,
        $serviceFactory,
        $serviceDataProvider,
        $convoServiceParamsFactory,
        $packageProviderFactory,
        $dialogflowApiFactory,
        $mediaService,
        $serviceReleaseManager
    )
    {
        parent::__construct( $logger, $user, $serviceId, $serviceDataProvider, $serviceReleaseManager);

        $this->_convoServiceFactory         =   $serviceFactory;
        $this->_convoServiceParamsFactory   =   $convoServiceParamsFactory;
        $this->_packageProviderFactory      =   $packageProviderFactory;
        $this->_dialogflowApiFactory        =   $dialogflowApiFactory;
        $this->_mediaService                =   $mediaService;
    }

    public function getPlatformId()
    {
        return DialogflowCommandRequest::PLATFORM_ID;
    }

    public function enable()
    {
        parent::enable();

        $config   =   $this->_convoServiceDataProvider->getServicePlatformConfig($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        $mode     =   strtoupper($config[$this->getPlatformId()]['mode'] ?? 'MANUAL');
        if ( $mode !== 'AUTO') {
            $this->_logger->debug( 'No propagation in ['.$mode.'] mode');
            return ;
        }

        $name = $config['dialogflow']['name'] ?? 'DefaultName';
        $name = $this->_camelCaseName($name);
        $desc = $config['dialogflow']['description'] ?? 'Demo agent';
        $defaultTimezone = $config['dialogflow']['default_timezone'];

        try {
            $avatar = $this->_mediaService->getMediaUrl(
                $this->_serviceId, $config['dialogflow']['avatar']
            );
        } catch (\Exception $e) {
            $this->_logger->warning($e->getMessage());
            $avatar = '';
        }

        $api = $this->_dialogflowApiFactory->getApi(
            $this->_user,
            $this->_serviceId
        );

        $existingAgent = $api->getAgent();
        if ($existingAgent !== null) {
            $config[$this->getPlatformId()]['name'] = $existingAgent->getDisplayName();
            $config[$this->getPlatformId()]['description'] = $existingAgent->getDescription();
            $config[$this->getPlatformId()]['avatar'] = $existingAgent->getAvatarUri();
            $config[$this->getPlatformId()]['default_timezone'] = $existingAgent->getTimeZone();
            $config[$this->getPlatformId()]['time_created'] = time();
            $config[$this->getPlatformId()]['time_updated'] = time();
            $this->_convoServiceDataProvider->updateServicePlatformConfig($this->_user, $this->_serviceId, $config);
        } else {
            $meta      =   $this->_convoServiceDataProvider->getServiceMeta( $this->_user, $this->_serviceId);
            $defaultLocale = $meta['default_language'];
            $supportedLocales = [];
            $agent = new Agent();
            $agent
                ->setDisplayName($name)
                ->setDefaultLanguageCode($defaultLocale)
                ->setSupportedLanguageCodes($supportedLocales)
                ->setTimeZone($defaultTimezone)
                ->setDescription($desc)
                ->setAvatarUri($avatar)
                ->setEnableLogging(true)
                ->setMatchMode(MatchMode::MATCH_MODE_UNSPECIFIED)
                ->setClassificationThreshold(0)
                ->setApiVersion(ApiVersion::API_VERSION_V2)
                ->setTier(Tier::TIER_STANDARD);

            $api->setAgent($agent);

            $export = $this->export();
            $api->restore($export->getContent());
            $api->trainAgent();
        }
    }

    public function propagate()
    {
		$config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user,
            $this->_serviceId,
		    IPlatformPublisher::MAPPING_TYPE_DEVELOP
		);

        $mode     =   strtoupper( $config[$this->getPlatformId()]['mode'] ?? 'MANUAL');
        if ( $mode !== 'AUTO') {
            $this->_logger->debug( 'No propagation in ['.$mode.'] mode');
            return ;
        }

        $api = $this->_dialogflowApiFactory->getApi(
            $this->_user,
            $this->_serviceId
		);

//		try {
//			$avatar = $this->_mediaService->getMediaUrl(
//				$this->_serviceId, $config['dialogflow']['avatar']
//			);
//			$this->_logger->debug('Got new avatar uri ['.$avatar.']');
//			$existing = $api->getAgent();
//			$existing->setAvatarUri($avatar);
//			$api->setAgent($existing);
//		} catch (\Exception $e) {
//			$this->_logger->warning($e->getMessage());
//		}

        $export = $this->export();
        $api->restore($export->getContent());
        $api->trainAgent();

        $this->_recordPropagation();
    }

    public function getPropagateInfo()
    {
        $data              =   parent::getPropagateInfo();
        $config            =   $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        if ( !isset( $config[$this->getPlatformId()])) {
            return $data;
        }

        $platform_config   =   $config[$this->getPlatformId()];

        if ( isset( $platform_config['mode']) && strtolower( $platform_config['mode']) === 'auto')
        {
            $data['allowed'] = true;

            $workflow  =   $this->_convoServiceDataProvider->getServiceData( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
            $meta      =   $this->_convoServiceDataProvider->getServiceMeta( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
            $alias     =   $this->_serviceReleaseManager->getDevelopmentAlias( $this->_user, $this->_serviceId, $this->getPlatformId());
            $mapping   =   $meta['release_mapping'][$this->getPlatformId()][$alias];

            if ( !isset( $mapping['time_propagated']) || empty( $mapping['time_propagated'])) {
                $data['available'] = true;
            } else {
                if ( $mapping['time_propagated'] < $platform_config['time_updated']) {
                    $this->_logger->debug( 'Config changed');
                    // TODO: check if propagatable properties are changed
                    $data['available'] = true;
                }

                if ( isset( $mapping['time_updated']) && ($mapping['time_propagated'] < $mapping['time_updated'])) {
                    $this->_logger->debug( 'Mapping changed');
                    $data['available'] = true;
                }

                if ( $mapping['time_propagated'] < $workflow['intents_time_updated']) {
                    $this->_logger->debug( 'Intents model changed');
                    $data['available'] = true;
                }

                if ($mapping['time_propagated'] < $workflow['time_updated']) {
                    $this->_logger->debug( 'Workflow changed');
                    $data['available'] = true;
                }

                if ($mapping['time_propagated'] < $meta['time_updated']) {
                    $this->_logger->debug( 'Meta changed');
                    $data['available'] = true;
                }
            }
        }

        return $data;
    }

	public function export()
    {
        $meta = $this->_convoServiceDataProvider->getServiceMeta( $this->_user, $this->_serviceId);

    	$service = $this->_convoServiceFactory->getService(
            $this->_user,
            $this->_serviceId,
            IPlatformPublisher::MAPPING_TYPE_DEVELOP,
            $this->_convoServiceParamsFactory
        );

        /** @var \Convo\Core\Intent\IIntentDriven $intent_drivens */
        $intent_drivens    =   $service->findChildren( '\Convo\Core\Intent\IIntentDriven');
        $provider = $this->_packageProviderFactory->getProviderFromPackageIds($service->getPackageIds());

        $intents           =   [];
        $entities          =   [];
        foreach ( $intent_drivens as $intent_driven) {

            /** @var $intent_driven IIntentDriven */
            $intent    =   $intent_driven->getPlatformIntentModel($this->getPlatformId());
            $intents[$intent->getName()] =   $intent;

            foreach ( $intent->getEntities() as $entity_name) {
                try {
                    $entity        =   $service->getEntity( $entity_name);
                } catch ( ComponentNotFoundException $e) {
                    $sys_entity    =   $provider->getEntity( $entity_name);
                    $entity        =   $sys_entity->getPlatformModel( $this->getPlatformId());
                }
                $entities[$entity->getName()] =   $entity;
            }
        }

        $data = [
            '.' => [],
            'entities' => [],
            'intents' => []
        ];

        $json_options_mask = JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

        $data['.'][] = new SimpleFileResource(
            'agent.json', 'application/json',
            json_encode($this->_createAgentManifest($meta), $json_options_mask, 1024)
        );

        $this->_logger->debug($data['.'][0]->getSize());

        $data['.'][] = new SimpleFileResource(
            'package.json', 'application/json',
            json_encode(['version' => '1.0.0'], $json_options_mask)
        );

        foreach ( $entities as $entity) {
            if ( $entity->isSystem()) {
                continue;
            }

			try
			{
				$catalog_name = $entity->getName().'Catalog';
				$this->_logger->debug('Going to try to look for catalog ['.$catalog_name.']');

				/** @var \Convo\Core\Workflow\ICatalogSource $catalog */
				$catalog = $service->findContext($catalog_name)->getComponent();
				$built = $this->_buildCatalogEntity($entity->getName(), $catalog);
			}
            catch (\Convo\Core\ComponentNotFoundException $cnfe)
			{
				$built = $this->_buildEntity($entity);
			}

            $entity_name = $built['definition']['name'];

            $definition = new SimpleFileResource(
                $entity_name.'.json', 'application/json',
                json_encode($built['definition'], $json_options_mask)
            );

            $defaultLocale = $meta['default_language'];
            $entries = new SimpleFileResource(
                $entity_name.'_entries_'.$defaultLocale.'.json', 'application/json',
                json_encode($built['entries'], $json_options_mask)
            );

            $data['entities'][] = $definition;
            $data['entities'][] = $entries;
        }

        $intents = array_merge($intents, [
            $provider->getIntent('convo-core.DefaultWelcomeIntent')->getPlatformModel($this->getPlatformId()),
            $provider->getIntent('convo-core.DefaultFallbackIntent')->getPlatformModel($this->getPlatformId()),
            $provider->getIntent('convo-core.DefaultRepromptIntent')->getPlatformModel($this->getPlatformId())
        ]);

        foreach ($intents as $intent) {
            $userSaysByLanguage = $meta['default_language'];
            $built = $this->_buildIntents($intent);

            $this->_logger->debug('Final built intent ['.print_r($built, true).']');

            $intent_name = $built['intent']['name'];

            $definition = new SimpleFileResource(
                $intent_name.'.json', 'application/json',
                json_encode($built['intent'], $json_options_mask)
            );

            $entries = new SimpleFileResource(
                $intent_name.'_usersays_'.$userSaysByLanguage.'.json', 'application/json',
                json_encode($built['usersays'], $json_options_mask)
            );

            $data['intents'][] = $definition;
            $data['intents'][] = $entries;
        }

        $export = new ZipFileResource(
            $this->_serviceId.'-'.$this->getPlatformId().'.zip',
            'application/zip',
            $data
        );

        return $export;
    }

    /**
     * Build DF intent from Convo model
     * @param \Convo\Core\Intent\IntentModel $intent
     * @return array
     */
    private function _buildIntents( $intent)
    {
        $utterances = [];

        foreach ( $intent->getUtterances() as $utterance) {
            $utterances[] = $this->_buildUtterance($utterance);
        }
        // When we build utterances, we generate them without the ID field
		// recursive merge should keep only existing IDs, everything else
		// needs to be overwritten

        $intent_data = $this->_buildIntent( $intent->getName(), $intent->getEvents(), $intent->isFallback(), $utterances);

        $final = [
            'intent' => $intent_data,
            'usersays' => $utterances
        ];

        return $final;
    }

    private function _buildIntent($name, $events, $isFallback, $utterances)
    {
        $unique = [];

        foreach ($utterances as $utterance) {
			foreach ($utterance['data'] as $data) {
				if (isset($data['alias']) && !isset($unique[$data['alias']])) {
					$unique[$data['alias']] = $data['meta'];
				}
			}
		}

        $intent = [
            'name' => $name,
            'auto' => true,
            'contexts' => [],
            'responses' => [
                [
                    'resetContexts' => false,
                    'parameters' => $this->_buildParamsFromUtterances($unique)
                ]
            ],
            'priority' => 500000,
            'webhookUsed' => true,
            'webhookForSlotFilling' => false,
            'fallbackIntent' => $isFallback
        ];

        if (!empty($events)) {
            $intent['events'] = $events;
        }

        return $intent;
    }

    private function _buildParamsFromUtterances($utterances)
    {
        $params = [];

        foreach ($utterances as $alias => $meta) {
            if (strpos($meta, '@') === false) {
                $meta = "@$meta";
            }

            $this->_logger->debug("Meta [$meta] => alias [$alias]");

            $params[] = [
                'required' => false,
                'dataType' => $meta,
                'name' => $alias,
                'value' => '$' . $alias,
                'isList' => false
            ];
        }

        return $params;
    }

    /**
     * @param \Convo\Core\Intent\IntentUtterance $convoUtterance
     * @return (array|false|int)[]
     * @throws \Exception
     */
    private function _buildUtterance($convoUtterance)
    {
        $utterance = [
            'data' => [],
            'isTemplate' => false,
            'updated' => 0,
            'count' => 0
        ];

        $last_part         =   null;
        $next_part         =   null;

        $parts             =    $convoUtterance->getParts();

        $provider = $this->_packageProviderFactory->getProviderByServiceId($this->_user, $this->_serviceId);

        for ( $index=0; $index<count( $parts); $index++)
        {
            $part   =   $parts[$index];
            if ( isset( $parts[$index+1])) {
                $next_part   =  $parts[$index+1];
            } else {
                $next_part   =   null;
            }

            $data = [
                'text' => $part['text']
            ];

            if ( isset($part['type']))
            {
                $data['userDefined'] = true;

                try {
                    $sys_def = $provider->getEntity(
                        substr($part['type'], 1));
                    $data['meta']   =   '@'.$sys_def->getPlatformName( 'dialogflow');
                    $data['meta']   =   str_replace( '@@', '@', $data['meta']);
                } catch ( \Convo\Core\ComponentNotFoundException $e) {
                    // todo quickfix
                    $data['meta'] = strpos( $part['type'], '@') === 0 ? $part['type'] : '@'.$part['type'];
                }

                $data['alias'] = $part['slot_value'];

                if ( isset( $last_part['type'])) {
                    $utterance['data'][] = [
                        'text' => ' ',
                        'userDefined' => false,
                    ];
                }
            }
            else
            {
                $pad_before     =   isset( $last_part['type']) ? ' ' : '';
                $pad_after      =   isset( $next_part['type']) ? ' ' : '';

                $data['text']   =   $pad_before.$part['text'].$pad_after;
                $data['userDefined'] = false;
            }

            $utterance['data'][] = $data;
            $last_part           = $part;
        }

        return $utterance;
    }

//     private function _aliasFromMeta($meta)
//     {
//         if (strpos($meta, '@sys.') === 0) {
//             return substr($meta, 5);
//         }

//         if (strpos($meta, '@') === 0) {
//             return substr($meta, 1);
//         }

//         throw new \Exception('Unexpected meta format ['.$meta.']');
//     }

    /**
     * Build DF entity out of Convo model
     * @param \Convo\Core\Intent\EntityModel $convoEntity
     * @return array
     */
    private function _buildEntity($convoEntity)
    {
    	$id =  StrUtil::uuidV4();
        $definition = [
        	'id' => $id,
            'name' => $convoEntity->getName(),
            'isOverridable' => true,
            'isEnum' => false,
            'isRegexp' => false,
            'automatedExpansion' => false,
            'allowFuzzyExtraction' => false
        ];

        $entries = [];

        foreach ($convoEntity->getValues() as $value)
        {
            $entries[] = [
                'value' => $value->getValue(),
                'synonyms' => $value->getSynonims()
            ];
        }

        return [
            'definition' => $definition,
            'entries' => $entries
        ];
    }

	/**
	 * Build DF entity out of a catalog entity, filled with all the values
	 * @param string $convoEntityName
	 * @param \Convo\Core\Workflow\ICatalogSource $catalog
	 * @return array
	 */
    private function _buildCatalogEntity($convoEntityName, $catalog)
	{
		$id = StrUtil::uuidV4();
		$definition = [
			'id' => $id,
			'name' => $convoEntityName,
			'isOverridable' => true,
			'isEnum' => false,
			'isRegexp' => false,
			'automatedExpansion' => false,
			'allowFuzzyExtraction' => false
		];

		$entries = [];

		foreach ($catalog->getCatalogValues($this->getPlatformId()) as $value)
		{
			$entries[] = [
				'value' => $value,
				'synonyms' => [$value]
			];
		}

		return [
			'definition' => $definition,
			'entries' => $entries
		];
	}

    // UTIL

    private function _createAgentManifest($meta)
    {
        $platform_config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );

        $df_config = $platform_config['dialogflow'];

        $agent = [
            'name' => $df_config['name'],
            'description' => $df_config['description'],
            'language' => $meta['default_language'],
            'supportedLanguages' => [],
            'disableInteractionLogs' => false,
            'disableStackdriverLogs' => true,
            'defaultTimezone' => $df_config['default_timezone'],
            'webhook' => [
                'url' => $this->_serviceReleaseManager->getWebhookUrl( $this->_user, $this->_serviceId, $this->getPlatformId()),
                'available' => true,
                'useForDomains' => true,
                'cloudFunctionsEnabled' => false,
                'cloudFunctionsInitialized' => false
            ],
            'isPrivate' => true,
            'customClassifierMode' => 'disabled',
            'mlMinConfidence' => 0.0,
            'onePlatformApiVersion' => 'v2',
            'analyzeQueryTextSentiment' => false,
            'dialogflowBuilderMode' => false
        ];

        return $agent;
    }

    private function _camelCaseName($name)
    {
        return str_replace(' ', '', ucwords($name));
    }


    private function _rmdir_recursive($dir)
	{
		$it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
		$it = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
		foreach($it as $file)
		{
			if ($file->isDir()) {
				rmdir($file->getPathname());
			}
			else {
				unlink($file->getPathname());
			}
		}
		rmdir($dir);
	}

	private function _filenameOnly($path)
	{
		return explode('.', $path)[0];
	}

	public function delete(array &$report)
    {
        $platform_config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );
        $mode = $platform_config[$this->getPlatformId()]['mode'] ?? 'manual';

        if ($mode === 'auto') {
            try {
                $api = $this->_dialogflowApiFactory->getApi(
                    $this->_user, $this->_serviceId
                );

                $api->deleteAgent();
                $report['successes'][$this->getPlatformId()]['service'] = 'Dialogflow agent successfully deleted.';
            } catch (\Exception $e) {
                $this->_logger->error($e);
                $report['errors'][$this->getPlatformId()]['service'] = $e->getMessage();
            }
        } else {
            $report['warnings'][$this->getPlatformId()]['service'] = "Dialogflow agent will not be deleted due to manual mode selection in the service platform configuration.";
        }
    }

    public function getStatus()
    {
        $status = ['status' => IPlatformPublisher::SERVICE_PROPAGATION_STATUS_IN_PROGRESS];
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user,
            $this->_serviceId,
            IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );

        if ($config[$this->getPlatformId()]['mode'] === 'manual') {
            $status['status'] = IPlatformPublisher::SERVICE_PROPAGATION_STATUS_FINISHED;
            return $status;
        }

        $api = $this->_dialogflowApiFactory->getApi(
            $this->_user,
            $this->_serviceId
        );
        $existingAgent = $api->getAgent();

        if ($existingAgent !== null) {
            $status['status'] = IPlatformPublisher::SERVICE_PROPAGATION_STATUS_FINISHED;
        }

        return $status;
    }
}
