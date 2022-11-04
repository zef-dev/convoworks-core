<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Intent\EntityModel;
use Convo\Core\Intent\IntentModel;
use Convo\Core\ComponentNotFoundException;
use Convo\Core\Publish\PlatformPublishingHistory;
use Convo\Core\Rest\InvalidRequestException;
use Convo\Core\Rest\ServiceBuildingException;
use Convo\Core\Util\StrUtil;
use Convo\Core\Workflow\IBasicServiceComponent;
use Convo\Core\Workflow\ICatalogSource;
use Convo\Core\Publish\IPlatformPublisher;
use Psr\Http\Client\ClientExceptionInterface;

class AlexaSkillPublisher extends \Convo\Core\Publish\AbstractServicePublisher
{
    const TYPE_SMALL_SKILL_URL = 'small_skill_icon';
    const TYPE_LARGE_SKILL_URL = 'large_skill_icon';

	/**
	 * @var string
	 */
	private $_publicRestBaseUrl;

	/**
	 * @var \Convo\Core\Factory\ConvoServiceFactory
	 */
	private $_convoServiceFactory;

	/**
	 * @var \Convo\Core\Params\IServiceParamsFactory
	 */
	private $_convoServiceParamsFactory;

	/**
	 * @var \Convo\Core\Adapters\Alexa\AmazonPublishingService
	 */
	private $_amazonPublishingService;

	/**
	 * @var \Convo\Core\Factory\PackageProviderFactory
	 */
	private $_packageProviderFactory;

	/**
	 * @var \Convo\Core\IAdminUserDataProvider
	 */
	private $_adminUserDataProvider;

    /**
     * @var \Convo\Core\Media\IServiceMediaManager
     */
    private $_mediaService;

    /**
     * @var PlatformPublishingHistory
     */
    private $_platformPublishingHistory;

	public function __construct(
		$publicRestBaseUrl,
		$logger,
		\Convo\Core\IAdminUser $user,
		$serviceId,
		$serviceFactory,
		$serviceDataProvider,
		$serviceParamsFactory,
		$amazonPublishingService,
		\Convo\Core\Factory\PackageProviderFactory $packageProviderFactory,
		$adminUserDataProvider,
	    $serviceReleaseManager,
        $mediaService,
        $platformPublishingHistory
		)
	{
	    parent::__construct($logger, $user, $serviceId, $serviceDataProvider, $serviceReleaseManager);

	    $this->_publicRestBaseUrl			=	$publicRestBaseUrl;
		$this->_convoServiceFactory			= 	$serviceFactory;
		$this->_convoServiceParamsFactory	= 	$serviceParamsFactory;
		$this->_amazonPublishingService		=	$amazonPublishingService;
		$this->_packageProviderFactory      =	$packageProviderFactory;
		$this->_adminUserDataProvider		=	$adminUserDataProvider;
        $this->_mediaService                =   $mediaService;
        $this->_platformPublishingHistory   =   $platformPublishingHistory;
	}

	public function getPlatformId()
	{
		return AmazonCommandRequest::PLATFORM_ID;
	}

	public function enable()
	{
	    parent::enable();
        $meta = $this->_getServiceMeta();
        $amazonUserConfig = $this->_getAmazonUserConfig();
        $amazonConfig = $this->_getAmazonConfig();

        if (!$this->_checkEnableOrPropagate($meta, $amazonUserConfig, $amazonConfig)) {
            return;
        }

        if (!empty($amazonConfig['app_id'])) {
            $this->_importExistingAlexaSkill($amazonConfig);
            return;
        }

		$this->_logger->debug('Going to go through enable procedure for ['.$this->_serviceId.']');

        $owner = $this->_adminUserDataProvider->findUser($meta['owner']);

		$vendorId = $amazonUserConfig['vendor_id'];
		$this->_logger->info("Going to print manifest [" . $this->_prepareManifestData([], true) . "]");

		$manifestToCreate = $this->_prepareManifestData();

		$res = $this->_amazonPublishingService->createSkill($owner, $vendorId, $manifestToCreate);

		$this->_logger->debug('Got res ['.print_r($res, true).']');

        $amazonConfig['app_id'] = $res['skillId'];
        $this->_updateAmazonConfig($amazonConfig);

		$this->_logger->debug('Going to poll until skill has been created.');
		$this->_amazonPublishingService->pollUntilSkillCreated($this->_user, $res['skillId']);
		$this->_logger->debug('Polling complete. Updating skill interaction model.');

        $warnings = [];
        // try to prepare the interaction model
        $model = [];
        $this->_logger->info('Trying to build convoworks model');
        try {
            $model = json_decode($this->export()->getContent(), true);
        } catch (\Exception $e) {
            $this->_logger->warning($e);
            $warnings[] = ['code' => $e->getCode(), 'message' => $e->getMessage()];
        }

        if (empty($model)) {
            $this->_logger->warning('Failed to export convo model');
            $warnings[] = ['code' => 'convoworks_model_export_failed', 'message' => "Failed to export interaction model."];
        } else {
            $buildFailures = 0;
            try {
                $this->_buildInteractionModel($owner, $res['skillId'], $meta['supported_locales'], $model);
            } catch (\Exception $e) {
                $buildErrors = json_decode($e->getMessage(), true);
                foreach ($buildErrors as $buildError) {
                    $buildFailures++;
                    $warnings[] = ['code' => $buildError['code'], 'message' => $buildError['message']];
                }
            }

            // try to and manage additional stuff like account linking management, upload self-signed certificate for skill API and so on
            if ($buildFailures < count($meta['supported_locales'])) {
                try {
                    $this->_uploadSelfSignedSslCertificateToAlexaSkill($amazonConfig, $owner, $res['skillId']);
                } catch (\Exception $e) {
                    $this->_logger->warning($e);
                    $warnings[] = ['code' => $e->getCode(), 'message' => $e->getMessage()];
                }

                try {
                    $this->_manageAccountLinking($owner, $res['skillId'], 'development', $amazonConfig);
                } catch (\Exception $e) {
                    $this->_logger->warning($e);
                    $warnings[] = ['code' => $e->getCode(), 'message' => $e->getMessage()];
                }
            }
        }

		$this->_recordPropagation();
        $this->_platformPublishingHistory->storePropagationData(
            $this->_serviceId,
            $this->getPlatformId(),
            $this->_preparePropagateData($model, $manifestToCreate)
        );

        if (!empty($warnings)) {
            throw new \Convo\Core\Adapters\Alexa\AlexaSkillPublisherWarningsOccurredException(json_encode($warnings));
        }
	}

	public function propagate()
	{
        $this->_checkOperationReady();

        $meta = $this->_getServiceMeta();
		$config = $this->_getAmazonConfig();
		$userConfig = $this->_getAmazonUserConfig();

        if (!$this->_checkEnableOrPropagate($meta, $userConfig, $config)) {
            return;
        }

		$owner = $this->_adminUserDataProvider->findUser($meta['owner']);
		$skillId = $config['app_id'];
        $locales = $meta['supported_locales'];
        $manifestToPropagate = $this->_prepareManifestData();
		$this->_logger->info('Updating skill manifest [' . json_encode($manifestToPropagate) . ']');

		$this->_amazonPublishingService->updateSkill(
			$owner, $skillId, 'development', ['manifest' => $manifestToPropagate ]
		);

        $model = json_decode($this->export()->getContent(), true);
        // throw new \Exception('Testing new dialog driven propagation. Exiting');

        $this->_buildInteractionModel($owner, $skillId, $locales, $model);

        $this->_uploadSelfSignedSslCertificateToAlexaSkill($config, $owner, $skillId);
        $this->_manageAccountLinking($owner, $skillId, 'development', $config);

        $this->_recordPropagation();
        $this->_platformPublishingHistory->storePropagationData(
            $this->_serviceId,
            $this->getPlatformId(),
            $this->_preparePropagateData($model)
        );
	}

	public function getPropagateInfo()
	{
	    $data              =   parent::getPropagateInfo();
        $platform_config            =   $this->_getAmazonConfig();

	    if ( empty( $platform_config)) {
	        $this->_logger->debug( 'No platform ['.$this->getPlatformId().'] config in service ['.$this->_serviceId.']. Exiting ... ');
	        return $data;
	    }

	    if ( isset( $platform_config['mode']) && strtolower( $platform_config['mode']) === 'auto')
	    {
	        $this->_logger->debug( 'Got auto mode. Checking further ... ');

	        $data['allowed'] = true;

	        $workflow  =   $this->_convoServiceDataProvider->getServiceData( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	        $meta      =   $this->_convoServiceDataProvider->getServiceMeta( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	        $alias     =   $this->_serviceReleaseManager->getDevelopmentAlias( $this->_user, $this->_serviceId, $this->getPlatformId());
	        $mapping   =   $meta['release_mapping'][$this->getPlatformId()][$alias];
	        $manifest  =   $this->_prepareManifestData();
            $model = [];
            try {
                $model = json_decode($this->export()->getContent(), true);
            } catch (\Exception $e) {
                $this->_logger->warning($e);
            }

	        $changesCount = 0;

	        if ( !isset( $mapping['time_propagated']) || empty( $mapping['time_propagated'])) {
	            $this->_logger->debug( 'Never propagated ');
	            $data['available'] = true;
	        } else {
	            if ( $mapping['time_propagated'] < $platform_config['time_updated']) {
	                $this->_logger->debug( 'Config changed');

                    $accountLinkingData = array_merge(
                        ['enable_account_linking' => $platform_config['enable_account_linking']],
                        $platform_config['account_linking_config']
                    );
	                $configChanged = $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
	                    $this->_serviceId, $this->getPlatformId(), PlatformPublishingHistory::AMAZON_ACCOUNT_LINKING_INFORMATION, $accountLinkingData
                    ) || $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
                            $this->_serviceId, $this->getPlatformId(), PlatformPublishingHistory::AMAZON_SELF_SIGNED_CERTIFICATE, $platform_config['self_signed_certificate']
                    ) || $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
                            $this->_serviceId, $this->getPlatformId(), PlatformPublishingHistory::AMAZON_ENDPOINT_SSL_CERTIFICATE_TYPE, $platform_config['endpoint_ssl_certificate_type']
                    ) || $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
                            $this->_serviceId, $this->getPlatformId(), PlatformPublishingHistory::AMAZON_MANIFEST, $manifest
                    ) || $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
							$this->_serviceId, $this->getPlatformId(), PlatformPublishingHistory::AMAZON_INTERACTION_MODEL, $model
						);
	                if ($configChanged) {
	                    $changesCount++;
                    }
	            }

	            if ( isset( $mapping['time_updated']) && ($mapping['time_propagated'] < $mapping['time_updated'])) {
	                $this->_logger->debug( 'Mapping changed');
	                $mappingChanged = true;
                    if ($mappingChanged) {
                        $changesCount++;
                    }
	            }

	            if ($mapping['time_propagated'] < $workflow['time_updated']) {
                    $this->_logger->debug( 'Workflow changed');
                    $workflowChanged = $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
                        $this->_serviceId, $this->getPlatformId(), PlatformPublishingHistory::AMAZON_INTERACTION_MODEL, $model
                    ) || $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
                            $this->_serviceId, $this->getPlatformId(), PlatformPublishingHistory::AMAZON_MANIFEST, $manifest
                    );
                    if ($workflowChanged) {
                        $changesCount++;
                    }
                }

                if ( $mapping['time_propagated'] < $workflow['intents_time_updated']) {
                    $this->_logger->debug( 'Intents model changed');
                    $intentsModelChanged = $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
                        $this->_serviceId, $this->getPlatformId(), PlatformPublishingHistory::AMAZON_INTERACTION_MODEL, $model
                    );
                    if ($intentsModelChanged) {
                        $changesCount++;
                    }
                }

                if ($mapping['time_propagated'] < $meta['time_updated']) {
                    $this->_logger->debug( 'Meta changed');
                    $metaChanged = $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
                        $this->_serviceId, $this->getPlatformId(), PlatformPublishingHistory::AMAZON_MANIFEST, $manifest
                    );
                    if ($metaChanged) {
                        $changesCount++;
                    }
                }

                if ($changesCount > 0) {
                    $data['available'] = true;
                }
	        }
	    }

	    return $data;
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Publish\IPlatformPublisher::export()
	 */
	public function export()
	{
		$service	=   $this->_convoServiceFactory->getService($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP, $this->_convoServiceParamsFactory);
		$sys_config = $this->_getAmazonUserConfig();
		$config = $this->_getAmazonConfig();

        $interfaces = $this->_prepareInterfacesFromWorkflowComponents();

        $meta = $this->_convoServiceDataProvider->getServiceMeta(
            $this->_user, $this->_serviceId
        );

        if (!$meta['owner']) {
            throw new \Exception('Could not determine service owner for service ['.$this->_serviceId.']');
        }

        $owner      =   $this->_adminUserDataProvider->findUser($meta['owner']);

        $skillId    =   $config['app_id'];

        if (!isset($sys_config['vendor_id'])) {
            throw new \Exception('Missing vendor ID for skill creation');
        }

        $vendorId   =   $sys_config['vendor_id'];

	    $stages     =   $this->_getSkillStages($owner, $vendorId, $skillId);

	    $this->_logger->debug('Got skill stages ['.print_r($stages, true).']');

	    $data          =   [
	        'interactionModel' => [
	            'languageModel' => [
	                'intents' => [],
	                'types' => [],
	            ]
	        ]
	    ];

        $invocation = $config['invocation'] ?? $this->_invocationToName($meta['name']);
        $data['interactionModel']['languageModel']['invocationName'] = strtolower($invocation);

	    if (isset($config['interaction_model_sensitivity'])) {
            $data['interactionModel']['languageModel']['modelConfiguration'] = [
                "fallbackIntentSensitivity" => [
                    "level" => strtoupper($config['interaction_model_sensitivity'])
                ]
            ];
        }

	    /** @var \Convo\Core\Intent\IIntentDriven[] $intent_drivens */
	    $intent_drivens    =   $service->findChildren( '\Convo\Core\Intent\IIntentDriven');

	    $intents           =   [];
	    $entities          =   [];
        $numberOfSystemIntents =   0;
        $numberOfCustomIntents =   0;

	    $provider = $this->_packageProviderFactory->getProviderFromPackageIds($service->getPackageIds());

	    foreach ( $intent_drivens as $intent_driven) {

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

	    foreach ( $entities as $entity) {
	        if ( $entity->isSystem()) {
	            continue;
	        }

			$this->_logger->debug('Going to try to look for catalog ['.$entity->getName().'Catalog]');

	        try
			{
			    $catalog_name = $entity->getName().'Catalog';
				/** @var \Convo\Core\Workflow\ICatalogSource $context */
			    $context = $service->findContext($catalog_name);

			    $this->_logger->debug('Got context ['.$context.']');

				$data['interactionModel']['languageModel']['types'][] = $this->_buildCatalogEntity($entity->getName(), $catalog_name, $context, $vendorId);
			}
	        catch (\Convo\Core\ComponentNotFoundException $cnfe)
			{
				$this->_logger->warning($cnfe->getMessage());
				$data['interactionModel']['languageModel']['types'][] = $this->_buildEntity($entity);
			}
	    }

	    foreach ( $intents as $intent) {
            /** @var IntentModel $intent */
            $numberOfSampleUtterances = count($intent->getUtterances());

            if ($intent->isSystem()) {
                $numberOfSystemIntents++;
                $data['interactionModel']['languageModel']['intents'][] = $this->_buildIntent( $intent);
            } else if (!$intent->isSystem() && $numberOfSampleUtterances > 0 ) {
                $numberOfCustomIntents++;
                $data['interactionModel']['languageModel']['intents'][] = $this->_buildIntent( $intent);
            } else {
                $this->_logger->warning( 'Skipping empty intent ['.$intent.']');
            }
	    }

	    $data['interactionModel']['languageModel']['intents'][]    =   [
	        "name" => "AMAZON.StopIntent",
	        "samples" => [],
	    ];

	    $data['interactionModel']['languageModel']['intents'][]    =   [
	        "name" => "AMAZON.NavigateHomeIntent",
	        "samples" => [],
	    ];

	    $data['interactionModel']['languageModel']['intents'][]    =   [
	        "name" => "AMAZON.HelpIntent",
	        "samples" => [],
	    ];

	    $data['interactionModel']['languageModel']['intents'][]    =   [
	        "name" => "AMAZON.CancelIntent",
	        "samples" => [],
	    ];

	    $data['interactionModel']['languageModel']['intents'][]    =   [
	        "name" => "AMAZON.FallbackIntent",
	        "samples" => [],
	    ];

	    if ($numberOfCustomIntents === 0) {
            $data['interactionModel']['languageModel']['intents'][]    =   [
                "name" => "HelloWorld",
                "samples" => ["hello world"],
            ];
        }

        /* REQUIRED FOR AUDIO_PLAYER */
        if (in_array("AUDIO_PLAYER", $interfaces)) {
            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.ResumeIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.PauseIntent",
                "samples" => [],
            ];
        }

        /* REQUIRED FOR DISPLAY */
        if (in_array("RENDER_TEMPLATE", $interfaces)) {
            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.ScrollLeftIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.ScrollRightIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.ScrollUpIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.ScrollDownIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.MoreIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.PageUpIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.PageDownIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][] = [
                "name" => "AMAZON.NavigateSettingsIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][]    =   [
                "name" => "AMAZON.NextIntent",
                "samples" => [],
            ];

            $data['interactionModel']['languageModel']['intents'][]    =   [
                "name" => "AMAZON.PreviousIntent",
                "samples" => [],
            ];
        }

	    $tmp   =   [];
	    $data['interactionModel']['languageModel']['intents'] = array_values( array_filter( $data['interactionModel']['languageModel']['intents'], function ( $item) use ( &$tmp) {
	        if ( isset( $tmp[$item['name']])) {
	            return false;
	        }
	        $tmp[$item['name']]    =   true;
	        return true;
	    }));

        /** @var \Convo\Core\Adapters\Alexa\IAlexaDialogDriven[] $intent_drivens */
        $dialog_drivens    =   $service->findChildren( '\Convo\Core\Adapters\Alexa\IAlexaDialogDriven');

        $dialogDefinitions = [];
        if (!empty($dialog_drivens)) {
            foreach ($dialog_drivens as $dialog_driven) {
                $dialogDefinitions[] = $dialog_driven->getDialogDefinition();
            }
        }

        $slotSamples = [];
        foreach ($dialogDefinitions as $dialogDefinition) {
            if (isset($dialogDefinition['slotSamples'])) {
                foreach ($dialogDefinition['slotSamples'] as $slotSample) {
                    if (isset(array_values($slotSample)[0])) {
                        $slotSamples[key($slotSample)] = array_values($slotSample)[0];
                    }
                }
            }
        }

        // attaching slot samples
        if (!empty($slotSamples)) {
            foreach($data['interactionModel']['languageModel']['intents'] as $intentIndex => $intent) {
                if (isset($intent['slots'])) {
                    foreach ($intent['slots'] as $slotIndex => $slot) {
                        if (isset($slotSamples[$slot['name']])) {
                            $data['interactionModel']['languageModel']['intents'][$intentIndex]['slots'][$slotIndex]['samples'] = $slotSamples[$slot['name']];
                        }
                    }
                }
            }
        }

        $dialogIntents = [];
        foreach ($dialogDefinitions as $dialogDefinition) {
            if (isset($dialogDefinition['dialogIntent'])) {
                $dialogIntents[] = $dialogDefinition['dialogIntent'];
            }
        }

        if (!empty($dialogIntents)) {
            $data['interactionModel']['dialog'] = [
                'intents' => $dialogIntents,
                // TODO: make this setting based on alexa skill manifest interfaces
                'delegationStrategy' => 'ALWAYS'
            ];
            $prompts = [];
            foreach ($dialogDefinitions as $dialogDefinition) {
                if (isset($dialogDefinition['prompts'])) {
                    foreach ($dialogDefinition['prompts'] as $prompt) {
                        $prompts[] = $prompt;
                    }
                }
            }
            $data['interactionModel']['prompts'] = $prompts;
        }

        // $this->_logger->debug('Got final Dialog Definitions '.json_encode($dialogDefinitions, JSON_PRETTY_PRINT));
        $this->_logger->debug('Got final model '.json_encode($data, JSON_PRETTY_PRINT));
	    $export    =   new \Convo\Core\Util\SimpleFileResource(
	        $this->_serviceId.'-'.$this->getPlatformId().'.json', 'application/json', json_encode( $data, JSON_PRETTY_PRINT));

	    return $export;
	}

    private function _updateAmazonConfig($data) {
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user,
            $this->_serviceId,
            IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );

        $config[$this->getPlatformId()] = array_merge($config[$this->getPlatformId()], $data);
        $config[$this->getPlatformId()]['time_updated'] = time();

        $this->_convoServiceDataProvider->updateServicePlatformConfig($this->_user, $this->_serviceId, $config);
    }

    private function _getAmazonUserConfig() {
        return isset($this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())[$this->getPlatformId()]) ?
            $this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())[$this->getPlatformId()] : [];
    }

    private function _getAmazonConfig() {
        return $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user,
            $this->_serviceId,
            IPlatformPublisher::MAPPING_TYPE_DEVELOP
        )[$this->getPlatformId()] ?? [];
    }

    private function _getServiceMeta() {
        return $this->_convoServiceDataProvider->getServiceMeta($this->_user, $this->_serviceId);
    }

    private function _checkEnableOrPropagate($serviceMeta, $amazonUserConfig, $amazonConfig) {
        $canEnableOrPropagate = true;
        if (!$serviceMeta['owner']) {
            throw new \Exception('Could not determine owner for service ['.$this->_serviceId.']');
        }

        if (!isset($amazonUserConfig['vendor_id'])) {
            throw new \Exception('Missing vendor ID for skill creation');
        }

        if (empty($amazonConfig)) {
            throw new \Exception('Missing amazon config');
        }

        $mode     =   strtoupper($amazonConfig['mode'] ?? 'MANUAL');
        if ( $mode !== 'AUTO') {
            $this->_logger->debug( 'No propagation in ['.$mode.'] mode');
            $canEnableOrPropagate = false;
        }

        if (empty(strtolower($amazonConfig['invocation']))) {
            throw new \Exception("Invocation can't be empty.");
        }

        return $canEnableOrPropagate;
    }

	/**
	 * @param IntentModel $intent
	 * @return array
	 */
	private function _buildIntent( $intent)
	{
	    $this->_logger->debug( 'Building intent ['.$intent.']');

	    if ( $intent->isSystem())
	    {
	        // it is the real system intent on the amazon
	        return [
	            'name' => $intent->getName(),
	            'samples' => []
	        ];
	    }

	    // build custom intent structure
	    $intent_data    =    [
	        'name' => $intent->getName(),
	        'slots' => [],
	        'samples' => [],
	    ];

	    $registered_slots  =   [];

	    $provider = $this->_packageProviderFactory->getProviderByServiceId($this->_user, $this->_serviceId);

	    foreach ( $intent->getUtterances() as $utterance)
	    {
// 	        {
// 	            "raw" : "I'll play guess the number",
// 	            "model" : [
// 	            { "text" : "I'll play"},
// 	            { "text" : "guess", "type" : "@GameType", "slot_value" : "selectGameCommand"},
// 	            { "text" : "the number"}
// 	            ]
// 	        }

	        $sentence          	=   [];
	        $part_count			=	count($utterance->getParts());

	        foreach ( $utterance->getParts() as $index => $part_data)
	        {
	            if ( isset( $part_data['type']) && $part_data['type'])
	            {
	                try {
	                    // find system intent if available to mapp propper platform name.
	                    $system_entity     =   $provider->getEntity( $part_data['type']);
	                    $entity_name       =   $system_entity->getPlatformName( \Convo\Core\Adapters\Alexa\AmazonCommandRequest::PLATFORM_ID);
	                } catch ( \Convo\Core\ComponentNotFoundException $e) {
	                    $entity_name       =   $part_data['type'];
					}

					$entity_name = strpos($entity_name, '@') !== false ? str_replace('@', '', $entity_name) : $entity_name;

	                if ( isset( $part_data['slot_value']) && $part_data['slot_value'] && !isset( $registered_slots[$part_data['slot_value']])) {
	                    $intent_data['slots'][]    =   [
	                        'name' => $part_data['slot_value'],
	                        'type' => $entity_name
	                    ];
	                    $registered_slots[$part_data['slot_value']]    =   $part_data['slot_value'];
	                }

	                // If this is the first and only value in an utterance, is a slot, and is of type SearchQuery,
					// add a space before the brackets. Otherwise we get an error.
	                if ($index === 0 && $part_count === 1 && $entity_name === 'AMAZON.SearchQuery')
					{
						$segment = ' {'.$part_data['slot_value'].'}';
					}
	                else
					{
						$segment = '{'.$part_data['slot_value'].'}';
					}
	                $sentence[] = $segment;
	            }
	            else
	            {
	                $sentence[] = $this->_sanitizeText($part_data['text']);
	            }
	        }

	        $intent_data['samples'][] =   implode( ' ', $sentence);
	    }

	    return $intent_data;
	}

	/**
	 * @param EntityModel $entity
	 * @return array
	 */
	private function _buildEntity( $entity)
	{
	    //         {
	    //             "name" : "GameType",
	    //             "values" : [
	    //                 {
	    //                     "value" : "pick",
	    //                     "synonyms" : [ "pick"]
	    //                 },
	    //                 {
	    //                     "value" : "guess",
	    //                     "synonyms" : [ "guess"]
	    //                 }
	    //             ]
	    //         },

	    $this->_logger->debug( 'Building entity ['.$entity.']');

	    $amz_entity   =   [
	        'name' => $entity->getName(),
	        'values' => [],
	    ];

	    foreach ( $entity->getValues() as $value) {
	        $amz_entity['values'][]    =   [
	            'name' => [
	                'value' => $value->getValue(),
	                'synonyms' => $value->getSynonims()
	            ]
	        ];
	    }
	    return $amz_entity;
	}

	/**
	 * @param string $entityName
     * @param string $catalogName
	 * @param ICatalogSource $context
	 * @param string $vendorId
	 * @return array
	 * @throws \Exception
	 */
	private function _buildCatalogEntity($entityName, $catalogName, $context, $vendorId)
	{
	// {
	//     "name": "string",
	//     "valueSupplier": {
	//         "type": "CatalogValueSupplier",
	//         "valueCatalog": {
	//             "id": "string",
	//             "version": "string"
	//         }
	//     }
	// }

		// @TODO: The handling of "our" catalog versions vs Amazon's catalog versions
		// isn't very robust. Currently we only check to see that the versions are different
		// during propagation. Maybe we need to enforce strict increments?

		$this->_logger->debug('Going to check if catalog ['.$catalogName.'] exists.');

		$config = $this->_getAmazonConfig();

		$dev_version = $this->_serviceReleaseManager->getDevelopmentAlias(
			$this->_user, $this->_serviceId, $this->getPlatformId()
		);

		$this->_logger->debug('Got development version alias ['.$dev_version.']');

		if (!isset($config['catalogs'][$catalogName][$dev_version]))
		{
			$this->_logger->warning('Catalog ['.$catalogName.'] not yet created.');

			$catalog_creation_res = $this->_amazonPublishingService->createCatalog(
				$this->_user,
				$vendorId,
				$catalogName,
				'Catalog of values for '.$entityName
			);

			$this->_logger->debug('Catalog creation response ['.print_r($catalog_creation_res, true).']');

			$catalog_id = $catalog_creation_res['catalogId'];

			$this->_logger->debug('Catalog ['.$catalog_id.'] created successfully.');

			$config['catalogs'][$catalogName][$dev_version] = [
				'time_created' => time(),
				'time_updated' => time(),
				'catalog_id' => $catalog_id
			];

            try {
                // try to crete a new catalog version
                $version = $this->_amazonPublishingService->createCatalogVersion(
                    $this->_user,
                    $catalog_id,
                    $this->_buildCatalogSourceUrl($catalogName, $dev_version),
                    StrUtil::uuidV4()
                );

                $this->_logger->debug('Catalog version created successfully');

                $config['catalogs'][$catalogName][$dev_version]['time_updated'] = time();
                $config['catalogs'][$catalogName][$dev_version]['version'] = $version['lastUpdateRequest']['version'];
                $this->_updateAmazonConfig($config);

                return [
                    "name" => $entityName,
                    "valueSupplier" => [
                        "type" => "CatalogValueSupplier",
                        "valueCatalog" => [
                            "catalogId" => $catalog_id,
                            "version" => $version['lastUpdateRequest']['version']
                        ]
                    ]
                ];
            } catch (\Exception $e) {
                // remove catalog without version
                $this->_amazonPublishingService->deleteCatalog($this->_user, $catalog_id);
                throw new \Exception($e->getMessage());
            }
		}
		else
		{
			$existing = $config['catalogs'][$catalogName][$dev_version];
			$v = $existing['version'];

			$this->_logger->debug('Catalog already exists ['.print_r($existing, true).']['.$context->getCatalogVersion().']');

			if (is_numeric($context->getCatalogVersion()) && $v !== $context->getCatalogVersion())
			{
				$this->_logger->debug('Stored catalog version does not match actual. Going to update new version');

				$version = $this->_amazonPublishingService->createCatalogVersion(
					$this->_user,
					$existing['catalog_id'],
					$this->_buildCatalogSourceUrl($catalogName, $dev_version),
					StrUtil::uuidV4()
				);

				$current_ver = $version['lastUpdateRequest']['version'];

				$this->_logger->info('Amazon catalog version ['.$current_ver.'] created successfully');

				$config['catalogs'][$catalogName][$dev_version]['time_updated'] = time();
				$config['catalogs'][$catalogName][$dev_version]['version'] = $context->getCatalogVersion();
				$this->_updateAmazonConfig($config);

				$v = $current_ver;
			}

			return [
				"name" => $entityName,
				"valueSupplier" => [
					"type" => "CatalogValueSupplier",
					"valueCatalog" => [
						"catalogId" => $existing['catalog_id'],
						"version" => $v
					]
				]
			];
		}
	}

	private function _buildCatalogSourceUrl($catalogName, $devVersion)
	{
		return $this->_publicRestBaseUrl.'/service-catalogs/'.$this->_serviceId.'/'.$catalogName.'/'.$devVersion.'/amazon';
	}

	private function _invocationToName($invocation)
	{
		return ucwords($invocation);
	}

	private function _getSkillStages(\Convo\Core\IAdminUser $user, $vendorId, $skillId)
    {
        $skills = $this->_amazonPublishingService->listSkills($user, $vendorId, [$skillId]);

        $available = [];

        foreach ($skills['skills'] as $skill)
        {
            $available[] = $skill['stage'];
        }

        return $available;
    }

    public function delete(array &$report)
    {
        $platform_config = $this->_getAmazonConfig();
        $skill_id = $platform_config['app_id'];
        $mode = $platform_config['mode'] ?? 'manual';

        if ($mode === 'auto') {
            try {
                $delete_res = $this->_amazonPublishingService->deleteSkill($this->_user, $skill_id);
                $this->_logger->info('Amazon skill ['.$skill_id.'] deleted successfully, response ['.print_r($delete_res, true).']');
                $this->_platformPublishingHistory->removeSoredPropagationData($this->_serviceId, $this->getPlatformId());
                $report['successes'][$this->getPlatformId()]['service'] = "Amazon skill $skill_id successfully deleted.";
            } catch (\Exception $e) {
                $this->_logger->error($e);
                $report['errors'][$this->getPlatformId()]['service'] = $e->getMessage();
            }

            if (isset($platform_config['catalogs']))
            {
                $this->_logger->info('Going to delete Amazon catalogs');

                foreach ($platform_config['catalogs'] as $catalog_name => $catalog)
                {
					$this->_logger->info('Deleting versions for catalog ['.$catalog_name.']');

                    foreach ($catalog as $version => $catalog_data)
					{
						try {
							$catalog_id = $catalog_data['catalog_id'];
							$catalog_delete_res = $this->_amazonPublishingService->deleteCatalog($this->_user, $catalog_id);

							$this->_logger->debug('Catalog ['.$catalog_name.'] deletion response ['.print_r($catalog_delete_res, true).']');

							$report['successes'][$this->getPlatformId()][$catalog_name] = "Catalog $catalog_name successfully deleted.";
						} catch (\Exception $e) {
							$this->_logger->error($e->getMessage()); //@todo Logging just $e breaks the next line. Buffer overflow?
							$report['errors'][$this->getPlatformId()][$catalog_name] = $e->getMessage();
						}
					}
                }
            }
        } else {
            $this->_logger->info('Will not delete service due to manual mode selection.');
            $report['warnings'][$this->getPlatformId()]['service'] = "Skill with ID [".$skill_id."] will not be deleted due to manual mode selection in the service platform configuration.";
        }
    }

    public function getStatus()
    {
        $status = ['status' => IPlatformPublisher::SERVICE_PROPAGATION_STATUS_IN_PROGRESS];

        $config = $this->_getAmazonConfig();

        if ($config['mode'] === 'manual') {
            return ['status' => IPlatformPublisher::SERVICE_PROPAGATION_STATUS_FINISHED];
        }

        $meta = $this->_convoServiceDataProvider->getServiceMeta(
            $this->_user, $this->_serviceId
        );

        $owner = $this->_adminUserDataProvider->findUser($meta['owner']);
        $skillId = $config['app_id'];

        $existingManifest = $this->_amazonPublishingService->getSkill($this->_user, $skillId, 'development');
        $manifestData = $existingManifest['manifest'];

        $skillStatus = $this->_amazonPublishingService->getSkillStatus($owner, $skillId);

        $this->_logger->debug('Got Alexa Skill status ['. json_encode($skillStatus).']');

        if (!isset($skillStatus['interactionModel'])) {
            return ['status' => IPlatformPublisher::SERVICE_PROPAGATION_STATUS_MISSING_INTERACTION_MODEL];
        }

        $finishedBuilds = 0;

        $localesFromExistingManifest = array_keys($manifestData['publishingInformation']['locales']);
        foreach ($localesFromExistingManifest as $locale) {
            if ($skillStatus['interactionModel'][$locale]['lastUpdateRequest']['status'] !== 'IN_PROGRESS') {
                $finishedBuilds++;
            }
        }
        $finishedBuilding = $finishedBuilds === count($localesFromExistingManifest);

        if ($finishedBuilding) {
            $status['status'] = IPlatformPublisher::SERVICE_PROPAGATION_STATUS_FINISHED;
        }

        return $status;
    }

	private function _getLatestPublicationStatus()
	{
		$config = $this->_getAmazonConfig();

		$meta = $this->_convoServiceDataProvider->getServiceMeta(
			$this->_user, $this->_serviceId
		);

		$skillId = $config['app_id'];
		$owner = $this->_adminUserDataProvider->findUser($meta['owner']);

		try {
			$latestPublicationStatusOfSkill = $this->_amazonPublishingService->getLatestPublicationStatusOfSkill($owner, $skillId)['status'] ?? 'UNDEFINED';
		} catch (ClientExceptionInterface $e) {
			if ($e->getCode() === 404) {
				$this->_logger->info('Skill with ID [' . $skillId . '] has not been published for certification yet.');
				$latestPublicationStatusOfSkill = 'NOT_PUBLISHED_FOR_CERTIFICATION_YET';
			} else {
				throw new \Convo\Core\Util\HttpClientException($e->getMessage(), $e->getCode(), $e);
			}
		}

		return $latestPublicationStatusOfSkill;
	}

    /**
     * @param $config
     * @param \Convo\Core\IAdminUser $owner
     * @param $skillId
     */
    private function _uploadSelfSignedSslCertificateToAlexaSkill($config, \Convo\Core\IAdminUser $owner, $skillId): void
    {
        if ($config['endpoint_ssl_certificate_type'] === AmazonSkillManifest::CERTIFICATE_TYPE_SELF_SIGNED) {
            if (isset($config['self_signed_certificate'])) {
                $sslCertificate = $config['self_signed_certificate'];
                $this->_logger->debug("Printing self signed ssl certificate [" . $sslCertificate . "] when [" . $config['endpoint_ssl_certificate_type'] . "] is selected." );
                $this->_amazonPublishingService->uploadSelfSignedSslCertificateToSkill($owner, $skillId, $sslCertificate);
            }
        }
    }

    private function _manageAccountLinking($owner, $skillId, $stage, $amazonConfig) {
        if (isset($amazonConfig['enable_account_linking']) && isset($amazonConfig['account_linking_config'])) {
			$accountLinkingMode = $amazonConfig['account_linking_mode'] ?? '';
			$clientId = $amazonConfig['account_linking_config']["client_id"] ?? "";
			$clientSecret = $amazonConfig['account_linking_config']["client_secret"] ?? "";

			if ($accountLinkingMode === 'installation') {
				$clientId = $this->_serviceId;
				$clientSecret = md5($clientId);
                $amazonConfig['account_linking_config']['client_id'] = $clientId;
                $amazonConfig['account_linking_config']['client_secret'] = $clientSecret;
                $amazonConfig['account_linking_config']['scopes'] = '';
                $this->_updateAmazonConfig($amazonConfig);
			}

            if ($amazonConfig['enable_account_linking']) {
                $body = [
                    "accountLinkingRequest" => [
                        "skipOnEnablement" => $amazonConfig['account_linking_config']["skip_on_enablement"] ?? false,
                        "type" => "AUTH_CODE",
                        "authorizationUrl" => $amazonConfig['account_linking_config']["authorization_url"] ?? "",
                        "domains" => isset($amazonConfig['account_linking_config']["domains"]) ? explode(";", $amazonConfig['account_linking_config']["domains"]) : [],
                        "scopes" => isset($amazonConfig['account_linking_config']["scopes"]) ? explode(";", $amazonConfig['account_linking_config']["scopes"]) : [],
                        "accessTokenUrl" => $amazonConfig['account_linking_config']["access_token_url"] ?? "",
                        "clientId" => $clientId,
                        "clientSecret" => $clientSecret,
                        "accessTokenScheme" => "HTTP_BASIC"
                    ]
                ];

                $this->_amazonPublishingService->enableAccountLinking($owner, $skillId, $stage, $body);
            } else {
                try {
                    $this->_amazonPublishingService->getAccountLinkingInformation($owner, $skillId, $stage);
                    $this->_amazonPublishingService->disableAccountLinking($owner, $skillId, $stage);
                } catch (ClientExceptionInterface $e) {
                    if ($e->getCode() !== 404) {
                        throw new \Exception($e->getMessage(), 0, $e);
                    } else {
                        $this->_logger->warning("Can't delete account linking partner with skill id [". $skillId . "] because it could not be found.");
                    }
                }
            }
        }
    }

    private function _getDownloadLink($serviceId, $mediaItem, $owner, $typeSkillIconUrl) {
        $iconUrl = '';

        if (!empty($mediaItem)) {
            try {
                $parsedUrl = parse_url($mediaItem);
                if (is_array($parsedUrl) && count($parsedUrl) > 1) {
                    $iconUrl = $mediaItem;
                } else if (is_array($parsedUrl) && count($parsedUrl) === 1) {
                    $iconUrl = $this->_mediaService->getMediaUrl($serviceId, $mediaItem);
                }
                $this->_amazonPublishingService->checkSkillIconAvailability($iconUrl, $owner);
                $imageSize = getimagesize($iconUrl);

                $width = $imageSize[0];
                $height = $imageSize[1];

                if ($typeSkillIconUrl === self::TYPE_SMALL_SKILL_URL) {
                    if ($width !== 108 && $height !== 108) {
                        throw new \Exception('Invalid dimensions for Small Skill Icon');
                    }
                } else if ($typeSkillIconUrl === self::TYPE_LARGE_SKILL_URL) {
                    if ($width !== 512 && $height !== 512) {
                        throw new \Exception('Invalid dimensions for Large Skill Icon');
                    }
                }
            } catch (ClientExceptionInterface $e) {
                $this->_logger->warning('Could not fetch image due to invalid Icon URL [' . $iconUrl . ']');
                $iconUrl = '';
            } catch (\Exception $e) {
                $this->_logger->warning($e->getMessage());
                $iconUrl = '';
            }
        } else {
            $this->_logger->warning('Icon URL could not be generated from the provided media item id [' . $mediaItem . ']');
        }

        return $iconUrl;
    }

    private function _checkOperationReady() {
        $skillStatus = $this->getStatus()['status'];
        $latestPublicationStatusOfSKill = $this->_getLatestPublicationStatus();
        if ($skillStatus === IPlatformPublisher::SERVICE_PROPAGATION_STATUS_IN_PROGRESS) {
            throw new ServiceBuildingException('Alexa Skill Interaction model is not finished yet building. Please try again later', 405);
        }
		if ($latestPublicationStatusOfSKill === 'IN_PROGRESS' || $latestPublicationStatusOfSKill === 'SCHEDULED') {
			throw new ServiceBuildingException('Alexa Skill is in certification process. To be able to propagate changes, please withdraw from certification.', 405);
		}
    }

    private function _sanitizeText($text) {
        return preg_replace('/[^a-zA-Z ._\'{}-]/', '', $text);
    }

    private function _prepareManifestData($existingManifest = [], $asJson = false) {

        if ($existingManifest !== []) {
            return $existingManifest;
        }

        $config = $this->_getAmazonConfig();

        $meta = $this->_getServiceMeta();

        $owner = $this->_adminUserDataProvider->findUser($meta['owner']);

        $locales = $meta['supported_locales'];
        $defaultLocale = $meta['default_locale'];

        if (isset($config['availability']['automatic_distribution']) && count($locales) <= 1) {
            $config['availability']['automatic_distribution'] = false;
            $this->_updateAmazonConfig($config);
        }

        $optInAutomaticDistribution = $config['availability']['automatic_distribution'] ?? false;

        $manifest = new AmazonSkillManifest();
        $manifest->setLogger($this->_logger);
        $interfaces = $this->_prepareInterfacesFromWorkflowComponents();

        if (count($interfaces) > 0) {
            $manifest->setInterfaces($interfaces);
        } else if (empty($interfaces)) {
            $manifest->clearInterfaces();
        }

        $endpointCertificate = isset($config['endpoint_ssl_certificate_type']) ? $config['endpoint_ssl_certificate_type'] :
            AmazonSkillManifest::CERTIFICATE_TYPE_WILDCARD;

        $smallSkillIcon = isset($config['skill_preview_in_store']['small_skill_icon']) ?
            $this->_getDownloadLink(
                $this->_serviceId,
                $config['skill_preview_in_store']['small_skill_icon'],
                $owner,
                self::TYPE_SMALL_SKILL_URL
            ) : '';

        $largeSkillIcon = isset($config['skill_preview_in_store']['large_skill_icon']) ?
            $this->_getDownloadLink(
                $this->_serviceId,
                $config['skill_preview_in_store']['large_skill_icon'],
                $owner,
                self::TYPE_LARGE_SKILL_URL
            ) : '';

		$permissions = $config['permissions'] ?? [];

        $public_name = $config['skill_preview_in_store']['public_name'] ?? $this->_invocationToName($meta['name']);
        $manifest->setGlobalEndpoint(
            $this->_serviceReleaseManager->getWebhookUrl(
                $this->_user, $this->_serviceId, $this->getPlatformId()
            )
        )->setName($locales, $public_name)
            ->setSummary($locales, $config['skill_preview_in_store']['one_sentence_description'])
            ->setDescription($locales, $config['skill_preview_in_store']['detailed_description'])
            ->setWhatsNew($locales, $config['skill_preview_in_store']['whats_new'])
            ->setSmallIconUri($locales, $smallSkillIcon)
            ->setLargeIconUri($locales, $largeSkillIcon)
            ->setKeywords($locales, explode(",", preg_replace('/\s+/', ',', $config['skill_preview_in_store']['keywords'])))
            ->setExamplePhrases($locales, $this->_formatExamplePhrases($config['skill_preview_in_store']['example_phrases']))
            ->setCategory($config['skill_preview_in_store']['category'])
            ->setTermsOfUseUrl($locales, $config['skill_preview_in_store']['terms_of_use_url'])
            ->setPrivacyPolicyUrl($locales, $config['skill_preview_in_store']['privacy_policy_url'])
            ->setTestingInstructions($config['privacy_and_compliance']['testing_instructions'])
            ->setDistributionMode(AmazonSkillManifest::DISTRIBUTION_MODE_PUBLIC)
            ->allowsPurchases($config['privacy_and_compliance']['allows_purchases'])
            ->usesPersonalInfo($config['privacy_and_compliance']['uses_personal_info'])
            ->isChildDirected($config['privacy_and_compliance']['is_child_directed'])
            ->containsAds($config['privacy_and_compliance']['contains_ads'])
            ->isExportCompliant($config['privacy_and_compliance']['is_export_compliant'])
            ->setTestingInstructions($config['privacy_and_compliance']['testing_instructions'])
            ->setOptInToAutomaticLocaleDistribution($optInAutomaticDistribution, $defaultLocale)
			->setPermissions($permissions)
            ->setGlobalCertificateType($endpointCertificate)
            ->setIsAvailableWorldwide(true);

        return $manifest->getManifest($asJson);
    }

	private function _formatExamplePhrases($configPhrases) {
		if (empty($configPhrases)) {
			return [];
		}

		$delimiter = strpos($configPhrases, ';') !== false ? ';' : "\n";

		return array_map(function ($line) { return rtrim($line); }, explode($delimiter, $configPhrases));
	}

    private function _preparePropagateData($model = [], $existingManifest = []) {
        $platform_config = $this->_getAmazonConfig();

        $accountLinkingData = array_merge(
            ['enable_account_linking' => $platform_config['enable_account_linking']],
            $platform_config['account_linking_config']
        );

        return [
            PlatformPublishingHistory::AMAZON_ACCOUNT_LINKING_INFORMATION => $accountLinkingData,
            PlatformPublishingHistory::AMAZON_SELF_SIGNED_CERTIFICATE => $platform_config['self_signed_certificate'] ?? null,
            PlatformPublishingHistory::AMAZON_ENDPOINT_SSL_CERTIFICATE_TYPE => $platform_config['endpoint_ssl_certificate_type'] ?? 'Wildcard',
            PlatformPublishingHistory::AMAZON_MANIFEST => $this->_prepareManifestData($existingManifest),
            PlatformPublishingHistory::AMAZON_INTERACTION_MODEL => $model
        ];
    }

	private function _prepareInterfacesFromWorkflowComponents() {
		$workflow  =   $this->_convoServiceDataProvider->getServiceData( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
		$interfaces = [];

		// get enabled packages in services
		$provider = $this->_packageProviderFactory->getProviderByServiceId($this->_user, $this->_serviceId);
		$servicePackages = $provider->getRow();
		$serviceComponentsWithInterfaces = [];

		// get service components that contain interfaces
		foreach ($servicePackages as $servicePackage) {
			if (isset($servicePackage['components'])) {
				foreach ($servicePackage['components'] as $serviceComponent) {
					if (isset($serviceComponent['component_properties']['_platform_defaults'][$this->getPlatformId()]['interfaces'])) {
						$serviceComponentsWithInterfaces[$serviceComponent['type']] =
							$serviceComponent['component_properties']['_platform_defaults'][$this->getPlatformId()]['interfaces'];
					}
				}
			}
		}

		$this->_logger->debug('Service components with interfaces [' . json_encode($serviceComponentsWithInterfaces, JSON_PRETTY_PRINT) . ']');

		array_walk_recursive($workflow, function ($value, $key) use ($serviceComponentsWithInterfaces, &$interfaces) {
			if ($key === 'class') {
				$this->_logger->debug('Checking class [' . $value . ']');
				if (isset($serviceComponentsWithInterfaces[$value])) {
					foreach ($serviceComponentsWithInterfaces[$value] as $componentInterface) {
						array_push($interfaces, $componentInterface);
					}
				}
			}
		});

		return array_unique($interfaces);
	}

    /**
     * @param $amazonConfig
     * @return bool
     */
    private function _importExistingAlexaSkill($amazonConfig): bool
    {
        $this->_logger->debug('Adapting existing Alexa Skill...');
        $existing = $this->_amazonPublishingService->getSkill($this->_user, $amazonConfig['app_id'], 'development');
        $manifestData = $existing['manifest'];
        $defaultLocale = array_keys($manifestData['publishingInformation']['locales'])[0];
        $interfaces = isset($manifestData['apis']['custom']['interfaces']) ? array_values($manifestData['apis']['custom']['interfaces']) : [];
        $sslCertificateType = isset($manifestData['apis']['custom']['endpoint']['sslCertificateType']) ?
            $manifestData['apis']['custom']['endpoint']['sslCertificateType'] : AmazonSkillManifest::CERTIFICATE_TYPE_WILDCARD;

        $amazonConfig['invocation'] = $manifestData['publishingInformation']['locales'][$defaultLocale]['name'];
        if (isset($amazonConfig['interfaces'])) {
            $amazonConfig['interfaces'] = array_map(function ($item) {
                return $item['type'];
            }, $interfaces);
        }

        $this->_logger->debug("Got existing ssl certificate type [" . $sslCertificateType . "]");
        if ($sslCertificateType === AmazonSkillManifest::CERTIFICATE_TYPE_SELF_SIGNED) {
            $selfSignedSslCertificateFromSkill = $this->_amazonPublishingService->getSelfSignedSslCertificateFromSkill($this->_user, $amazonConfig['app_id']);
            $this->_logger->debug("Showing existing ssl certificate [" . $selfSignedSslCertificateFromSkill . "]");
            if ($selfSignedSslCertificateFromSkill !== null) {
                $certFile = $this->_serviceId . "_cert.pem";
                $this->_logger->debug("Going to create cert file [" . $certFile . "]");
                $amazonConfig['endpoint_ssl_certificate_type'] = $sslCertificateType;
                $amazonConfig['self_signed_certificate'] = $selfSignedSslCertificateFromSkill;
            } else {
                $amazonConfig['endpoint_ssl_certificate_type'] = AmazonSkillManifest::CERTIFICATE_TYPE_WILDCARD;
                $amazonConfig['self_signed_certificate'] = null;
            }
        }

        try {
            $accountLinkingResponse = $this->_amazonPublishingService->getAccountLinkingInformation($this->_user, $amazonConfig['app_id'], 'development');
            $amazonConfig['enable_account_linking'] = true;
            $amazonConfig['account_linking_config']['skip_on_enablement'] = $accountLinkingResponse['skipOnEnablement'];
            $amazonConfig['account_linking_config']['authorization_url'] = $accountLinkingResponse['authorizationUrl'];
            $amazonConfig['account_linking_config']['access_token_url'] = $accountLinkingResponse['accessTokenUrl'];
            $amazonConfig['account_linking_config']['client_id'] = $accountLinkingResponse['clientId'];
            $amazonConfig['account_linking_config']['scopes'] = $accountLinkingResponse['scopes'];
            $amazonConfig['account_linking_config']['domains'] = $accountLinkingResponse['domains'];
        } catch (ClientExceptionInterface $e) {
            if ($e->getCode() !== 404) {
                throw new \Exception($e->getMessage(), 0, $e);
            } else {
                $this->_logger->warning("Can't get account linking partner with skill id [". $amazonConfig['app_id'] . "] because it could not be found.");
            }
        }

        $this->_updateAmazonConfig($amazonConfig);
        $this->_platformPublishingHistory->storePropagationData(
            $this->_serviceId,
            $this->getPlatformId(),
            $this->_preparePropagateData([], $manifestData)
        );
    }

    /**
     * @param $locales
     * @param \Convo\Core\IAdminUser $owner
     * @param $skillId
     * @param $model
     * @return void
     */
    private function _buildInteractionModel(\Convo\Core\IAdminUser $owner, $skillId, $locales, $model): void
    {
        $this->_logger->info('Going to Alexa build interaction model');
        $buildErrors = [];
        foreach ($locales as $locale) {
            try {
                $interaction_model_update_res = $this->_amazonPublishingService->updateInteractionModel(
                    $owner, $skillId, $model, $locale
                );
                $this->_logger->debug('Updated interaction model for [' . $locale . '], res [' . print_r($interaction_model_update_res, true) . ']');
            } catch (\Exception $e) {
                $this->_logger->warning($e);
                $buildErrors[] = ['code' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }

        if (!empty($buildErrors)) {
            throw new \Exception(json_encode($buildErrors));
        }
    }
}
