<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Intent\EntityModel;
use Convo\Core\Intent\IntentModel;
use Convo\Core\ComponentNotFoundException;
use Convo\Core\Rest\InvalidRequestException;
use Convo\Core\Util\SimpleFileResource;
use Convo\Core\Util\StrUtil;
use Convo\Core\Workflow\ICatalogSource;
use Convo\Core\Publish\IPlatformPublisher;
use Psr\Http\Client\ClientExceptionInterface;

class AlexaSkillPublisher extends \Convo\Core\Publish\AbstractServicePublisher
{
    const PLACEHOLDER_SMALL_SKILL_URL = 'https://via.placeholder.com/108.png/09f/fffC/O';

    const PLACEHOLDER_LARGE_SKILL_URL = 'https://via.placeholder.com/512.png/09f/fffC/O';

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
        $mediaService
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
	}

	public function getPlatformId()
	{
		return AmazonCommandRequest::PLATFORM_ID;
	}

	public function enable()
	{
	    parent::enable();
	    $meta = $this->_convoServiceDataProvider->getServiceMeta($this->_user, $this->_serviceId);

		if (!$meta['owner']) {
			throw new \Exception('Could not determine owner for service ['.$this->_serviceId.']');
		}

		$config = $this->_convoServiceDataProvider->getServicePlatformConfig(
			$this->_user,
			$this->_serviceId,
		    IPlatformPublisher::MAPPING_TYPE_DEVELOP
		);

		$mode     =   strtoupper($config[$this->getPlatformId()]['mode'] ?? 'MANUAL');
		if ( $mode !== 'AUTO') {
		    $this->_logger->debug( 'No propagation in ['.$mode.'] mode');
		    return ;
		}

        if ($config[$this->getPlatformId()]['app_id']) {
            $existing = $this->_amazonPublishingService->getSkill($this->_user, $config[$this->getPlatformId()]['app_id'], 'development');
            $manifestData = $existing['manifest'];
            $defaultLocale = array_keys($manifestData['publishingInformation']['locales'])[0];
            $interfaces = isset($manifestData['apis']['custom']['interfaces']) ? array_values($manifestData['apis']['custom']['interfaces']) : [];
            $sslCertificateType = isset($manifestData['apis']['custom']['endpoint']['sslCertificateType']) ?
                $manifestData['apis']['custom']['endpoint']['sslCertificateType'] : AmazonSkillManifest::CERTIFICATE_TYPE_WILDCARD;

            $config[$this->getPlatformId()]['invocation'] = $manifestData['publishingInformation']['locales'][$defaultLocale]['name'];
            if (isset($config[$this->getPlatformId()]['interfaces'])) {
                $config[$this->getPlatformId()]['interfaces'] = array_map(function ( $item) { return $item['type']; }, $interfaces);
            }
            $this->_logger->debug("Showing existing ssl certificate type [" . $sslCertificateType . "]");
            if ($sslCertificateType === AmazonSkillManifest::CERTIFICATE_TYPE_SELF_SIGNED) {
                $selfSignedSslCertificateFromSkill = $this->_amazonPublishingService->getSelfSignedSslCertificateFromSkill($this->_user, $config[$this->getPlatformId()]['app_id']);
                $this->_logger->debug("Showing existing ssl certificate [" . $selfSignedSslCertificateFromSkill . "]");
                if ($selfSignedSslCertificateFromSkill !== null) {
                    $certFile = $this->_serviceId . "_cert.pem";
                    $this->_logger->debug("Going to create cert file [" . $certFile . "]");
                    $config[$this->getPlatformId()]['endpoint_ssl_certificate_type'] = $sslCertificateType;
                    $file = new SimpleFileResource(
                        $certFile,
                        "application/octet-stream",
                        $selfSignedSslCertificateFromSkill
                    );
                    $config[$this->getPlatformId()]['self_signed_certificate'] = $this->_mediaService->saveMediaItem($this->_serviceId, $file);
                } else {
                    $config[$this->getPlatformId()]['endpoint_ssl_certificate_type'] = AmazonSkillManifest::CERTIFICATE_TYPE_WILDCARD;
                    $config[$this->getPlatformId()]['self_signed_certificate'] = null;
                }
            }
            try {
                $accountLinkingResponse = $this->_amazonPublishingService->getAccountLinkingInformation($this->_user, $config[$this->getPlatformId()]['app_id'], 'development');
                $config[$this->getPlatformId()]['enable_account_linking'] = true;
                $config[$this->getPlatformId()]['account_linking_config']['skip_on_enablement'] = $accountLinkingResponse['skipOnEnablement'];
                $config[$this->getPlatformId()]['account_linking_config']['authorization_url'] = $accountLinkingResponse['authorizationUrl'];
                $config[$this->getPlatformId()]['account_linking_config']['access_token_url'] = $accountLinkingResponse['accessTokenUrl'];
                $config[$this->getPlatformId()]['account_linking_config']['client_id'] = $accountLinkingResponse['clientId'];
                $config[$this->getPlatformId()]['account_linking_config']['scopes'] = $accountLinkingResponse['scopes'];
                $config[$this->getPlatformId()]['account_linking_config']['domains'] = $accountLinkingResponse['domains'];
            } catch (ClientExceptionInterface $e) {
                if ($e->getCode() !== 404) {
                    throw new \Exception($e->getMessage(), 0, $e);
                } else {
                    $this->_logger->warning("Can't get account linking partner with skill id [". $config[$this->getPlatformId()]['app_id'] . "] because it could not be found.");
                }
            }
            $config[$this->getPlatformId()]['time_updated'] = time();
            $this->_convoServiceDataProvider->updateServicePlatformConfig($this->_user, $this->_serviceId, $config);
            return;
        }

		$sys_config = isset($this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())[$this->getPlatformId()]) ?
                      $this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())[$this->getPlatformId()] : [];

		if (!isset($sys_config['vendor_id'])) {
			throw new \Exception('Missing vendor ID for skill creation');
		}

		$this->_logger->debug('Going to go through enable procedure for ['.$this->_serviceId.']');

		$manifest = new AmazonSkillManifest();
		$manifest->setLogger($this->_logger);

		$invocation = strtolower($config[$this->getPlatformId()]['invocation']);

		if (empty($invocation)) {
		    throw new \Exception("Invocation can't be empty.");
        }

		$locales = $meta['supported_locales'];
		$defaultLocale = $meta['default_locale'];
		$optInAutomaticDistribution = isset($config[$this->getPlatformId()]['availability']['automatic_distribution']) ?
            $config[$this->getPlatformId()]['availability']['automatic_distribution'] : true;

        $interfaces = isset($config[$this->getPlatformId()]['interfaces']) ? $config[$this->getPlatformId()]['interfaces'] : [];

        if (count($interfaces) > 0) {
            $manifest->setInterfaces($interfaces);
        }

        $owner = $this->_adminUserDataProvider->findUser($meta['owner']);

        $smallSkillIcon = isset($config[$this->getPlatformId()]['skill_preview_in_store']['small_skill_icon']) ?
            $this->_getDownloadLink(
                $this->_serviceId,
                $config[$this->getPlatformId()]['skill_preview_in_store']['small_skill_icon'],
                $owner,
                self::PLACEHOLDER_SMALL_SKILL_URL
            ) : self::PLACEHOLDER_SMALL_SKILL_URL;

        $largeSkillIcon = isset($config[$this->getPlatformId()]['skill_preview_in_store']['large_skill_icon']) ?
            $this->_getDownloadLink(
                $this->_serviceId,
                $config[$this->getPlatformId()]['skill_preview_in_store']['large_skill_icon'],
                $owner,
                self::PLACEHOLDER_LARGE_SKILL_URL
            ) : self::PLACEHOLDER_LARGE_SKILL_URL;

        $endpointCertificate = isset($config[$this->getPlatformId()]['endpoint_ssl_certificate_type']) ? $config[$this->getPlatformId()]['endpoint_ssl_certificate_type'] :
            AmazonSkillManifest::CERTIFICATE_TYPE_WILDCARD;
		$manifest
			->setName($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['public_name'])
			->setSummary($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['one_sentence_description'])
			->setDescription($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['detailed_description'])
			->setExamplePhrases($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['example_phrases'])
			->setKeywords($locales, preg_replace('/\s+/', ',', $config[$this->getPlatformId()]['skill_preview_in_store']['keywords']))
			->setSmallIconUri($locales, $smallSkillIcon)
			->setLargeIconUri($locales, $largeSkillIcon)
			->setCategory($config[$this->getPlatformId()]['skill_preview_in_store']['category'])
            ->setTermsOfUseUrl($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['terms_of_use_url'])
            ->setPrivacyPolicyUrl($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['privacy_policy_url'])
			->allowsPurchases($config[$this->getPlatformId()]['privacy_and_compliance']['allows_purchases'])
			->usesPersonalInfo($config[$this->getPlatformId()]['privacy_and_compliance']['uses_personal_info'])
			->isChildDirected($config[$this->getPlatformId()]['privacy_and_compliance']['is_child_directed'])
			->containsAds($config[$this->getPlatformId()]['privacy_and_compliance']['contains_ads'])
			->isExportCompliant($config[$this->getPlatformId()]['privacy_and_compliance']['is_export_compliant'])
			->setTestingInstructions($config[$this->getPlatformId()]['privacy_and_compliance']['testing_instructions'])
			->setIsAvailableWorldwide(true)
			->setDistributionMode(AmazonSkillManifest::DISTRIBUTION_MODE_PUBLIC)
            ->setOptInToAutomaticLocaleDistribution($optInAutomaticDistribution, $defaultLocale)
			->setGlobalEndpoint( $this->_serviceReleaseManager->getWebhookUrl( $this->_user, $this->_serviceId, $this->getPlatformId()))
			->setGlobalCertificateType($endpointCertificate);

		$vendorId = $sys_config['vendor_id'];
		$this->_logger->info("Going to print manifest [" . $manifest->getManifest(true) . "]");

		$manifestToCreate = $manifest->getManifest();
        if (!in_array('en-US', $locales)) {
            unset($manifestToCreate['publishingInformation']['locales']['en-US']);
        }

		$res = $this->_amazonPublishingService->createSkill($owner, $vendorId, $manifestToCreate);

		$this->_logger->debug('Got res ['.print_r($res, true).']');

		$config[$this->getPlatformId()]['app_id'] = $res['skillId'];
		$config[$this->getPlatformId()]['time_updated'] = time();
		$this->_convoServiceDataProvider->updateServicePlatformConfig($this->_user, $this->_serviceId, $config);

		$this->_logger->debug('Going to poll until skill has been created.');
		$this->_amazonPublishingService->pollUntilSkillCreated($this->_user, $res['skillId']);
		$this->_logger->debug('Polling complete. Updating skill interaction model.');

		$model = json_decode($this->export()->getContent(), true);

        foreach ($locales as $locale) {
            try {
                $interaction_model_update_res = $this->_amazonPublishingService->updateInteractionModel(
                    $owner, $res['skillId'], $model, $locale
                );
                $this->_logger->debug('Updated interaction model for [' . $locale . '], res ['.print_r($interaction_model_update_res, true).']');
            } catch (ClientExceptionInterface $e) {
                $report = ['errors' => []];
                $report['errors']['convoworks']['skill'] = "Interaction model couldn't be created, going to delete skill with id [" . $res['skillId'] . "]";
                $this->delete($report);
                throw new InvalidRequestException($e->getMessage(), 0, $e);
            }
        }

        $this->_uploadSelfSignedSslCertificateToAlexaSkill($config[$this->getPlatformId()], $owner, $res['skillId']);
        $this->_manageAccountLinking($owner, $res['skillId'], 'development', $config[$this->getPlatformId()]);
        // TODO pool as loong as skill status gets other then IN_PROGRESS
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


		$meta = $this->_convoServiceDataProvider->getServiceMeta(
			$this->_user, $this->_serviceId
		);

		if (!$meta['owner']) {
			throw new \Exception('Could not determine service owner for service ['.$this->_serviceId.']');
		}

		$owner = $this->_adminUserDataProvider->findUser($meta['owner']);

		$skillId = $config[$this->getPlatformId()]['app_id'];

        $locales = $meta['supported_locales'];
        $defaultLocale = $meta['default_locale'];
        $optInAutomaticDistribution = isset($config[$this->getPlatformId()]['availability']['automatic_distribution']) ?
            $config[$this->getPlatformId()]['availability']['automatic_distribution'] : true;;

		$manifest = new AmazonSkillManifest();
		$manifest->setLogger($this->_logger);
        $interfaces = isset($config[$this->getPlatformId()]['interfaces']) ? $config[$this->getPlatformId()]['interfaces'] : [];

        if (count($interfaces) > 0) {
            $manifest->setInterfaces($interfaces);
        } else if (empty($interfaces)) {
            $manifest->clearInterfaces();
        }

        $endpointCertificate = isset($config[$this->getPlatformId()]['endpoint_ssl_certificate_type']) ? $config[$this->getPlatformId()]['endpoint_ssl_certificate_type'] :
            AmazonSkillManifest::CERTIFICATE_TYPE_WILDCARD;

        $smallSkillIcon = isset($config[$this->getPlatformId()]['skill_preview_in_store']['small_skill_icon']) ?
            $this->_getDownloadLink(
                $this->_serviceId,
                $config[$this->getPlatformId()]['skill_preview_in_store']['small_skill_icon'],
                $owner,
                self::PLACEHOLDER_SMALL_SKILL_URL
            ) : self::PLACEHOLDER_SMALL_SKILL_URL;

        $largeSkillIcon = isset($config[$this->getPlatformId()]['skill_preview_in_store']['large_skill_icon']) ?
            $this->_getDownloadLink(
                $this->_serviceId,
                $config[$this->getPlatformId()]['skill_preview_in_store']['large_skill_icon'],
                $owner,
                self::PLACEHOLDER_LARGE_SKILL_URL
            ) : self::PLACEHOLDER_LARGE_SKILL_URL;

        $manifest->setGlobalEndpoint(
            $this->_serviceReleaseManager->getWebhookUrl(
                $this->_user, $this->_serviceId, $this->getPlatformId()
            )
        )->setName($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['public_name'])
            ->setSummary($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['one_sentence_description'])
            ->setDescription($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['detailed_description'])
            ->setWhatsNew($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['whats_new'])
            ->setSmallIconUri($locales, $smallSkillIcon)
            ->setLargeIconUri($locales, $largeSkillIcon)
            ->setKeywords($locales, explode(",", preg_replace('/\s+/', ',', $config[$this->getPlatformId()]['skill_preview_in_store']['keywords'])))
            ->setExamplePhrases($locales, explode(";", $config[$this->getPlatformId()]['skill_preview_in_store']['example_phrases']))
            ->setCategory($config[$this->getPlatformId()]['skill_preview_in_store']['category'])
            ->setTermsOfUseUrl($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['terms_of_use_url'])
            ->setPrivacyPolicyUrl($locales, $config[$this->getPlatformId()]['skill_preview_in_store']['privacy_policy_url'])
            ->setTestingInstructions($config[$this->getPlatformId()]['privacy_and_compliance']['testing_instructions'])
            ->setDistributionMode(AmazonSkillManifest::DISTRIBUTION_MODE_PUBLIC)
            ->allowsPurchases($config[$this->getPlatformId()]['privacy_and_compliance']['allows_purchases'])
            ->usesPersonalInfo($config[$this->getPlatformId()]['privacy_and_compliance']['uses_personal_info'])
            ->isChildDirected($config[$this->getPlatformId()]['privacy_and_compliance']['is_child_directed'])
            ->containsAds($config[$this->getPlatformId()]['privacy_and_compliance']['contains_ads'])
            ->isExportCompliant($config[$this->getPlatformId()]['privacy_and_compliance']['is_export_compliant'])
            ->setTestingInstructions($config[$this->getPlatformId()]['privacy_and_compliance']['testing_instructions'])
            ->setOptInToAutomaticLocaleDistribution($optInAutomaticDistribution, $defaultLocale)
            ->setGlobalCertificateType($endpointCertificate)
            ->setIsAvailableWorldwide(true);

        if (!in_array('en-US', $locales)) {
            unset($manifest['publishingInformation']['locales']['en-US']);
        }

		$this->_amazonPublishingService->updateSkill(
			$owner, $skillId, 'development', ['manifest' => $manifest->getManifest() ]
		);

        $model = json_decode($this->export()->getContent(), true);
        foreach ($locales as $locale) {
            $this->_amazonPublishingService->updateInteractionModel(
                $owner, $skillId, $model, $locale
            );
        }

        $this->_uploadSelfSignedSslCertificateToAlexaSkill($config[$this->getPlatformId()], $owner, $skillId);
        $this->_manageAccountLinking($owner, $skillId, 'development', $config[$this->getPlatformId()]);
        // TODO pool as loong as skill status gets other then IN_PROGRESS

		$this->_recordPropagation();
	}

	public function getPropagateInfo()
	{
	    $data              =   parent::getPropagateInfo();
	    $config            =   $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

	    if ( !isset( $config[$this->getPlatformId()])) {
	        $this->_logger->debug( 'No platform ['.$this->getPlatformId().'] config in service ['.$this->_serviceId.']. Exiting ... ');
	        return $data;
	    }

	    $platform_config   =   $config[$this->getPlatformId()];

	    if ( isset( $platform_config['mode']) && strtolower( $platform_config['mode']) === 'auto')
	    {
	        $this->_logger->debug( 'Got auto mode. Checking further ... ');

	        $data['allowed'] = true;

	        $workflow  =   $this->_convoServiceDataProvider->getServiceData( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	        $meta      =   $this->_convoServiceDataProvider->getServiceMeta( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	        $alias     =   $this->_serviceReleaseManager->getDevelopmentAlias( $this->_user, $this->_serviceId, $this->getPlatformId());
	        $mapping   =   $meta['release_mapping'][$this->getPlatformId()][$alias];

	        if ( !isset( $mapping['time_propagated']) || empty( $mapping['time_propagated'])) {
	            $this->_logger->debug( 'Never propagated ');
	            $data['available'] = true;
	        } else {
	            if ( $mapping['time_propagated'] < $platform_config['time_updated']) {
	                $this->_logger->debug( 'Config changed');
	                // 1589547443
	                // 1589547443
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

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Publish\IPlatformPublisher::export()
	 */
	public function export()
	{
	    $service	=   $this->_convoServiceFactory->getService($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP, $this->_convoServiceParamsFactory);
		$sys_config = isset($this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())[$this->getPlatformId()]) ?
                      $this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())[$this->getPlatformId()] : [];;
                      $config		=   $this->_convoServiceDataProvider->getServicePlatformConfig($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        $interfaces = isset($config[$this->getPlatformId()]['interfaces']) ? $config[$this->getPlatformId()]['interfaces'] : [];

        $meta = $this->_convoServiceDataProvider->getServiceMeta(
            $this->_user, $this->_serviceId
        );

        if (!$meta['owner']) {
            throw new \Exception('Could not determine service owner for service ['.$this->_serviceId.']');
        }

        $owner      =   $this->_adminUserDataProvider->findUser($meta['owner']);

        $skillId    =   $config[$this->getPlatformId()]['app_id'];

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

        $invocation = $config['amazon']['invocation'] ?? $this->_invocationToName($meta['name']);
        $data['interactionModel']['languageModel']['invocationName'] = strtolower($invocation);

	    if (isset($config[AmazonCommandRequest::PLATFORM_ID]['interaction_model_sensitivity'])) {
            $data['interactionModel']['languageModel']['modelConfiguration'] = [
                "fallbackIntentSensitivity" => [
                    "level" => strtoupper($config[AmazonCommandRequest::PLATFORM_ID]['interaction_model_sensitivity'])
                ]
            ];
        }

	    /** @var \Convo\Core\Intent\IIntentDriven $intent_drivens */
	    $intent_drivens    =   $service->findChildren( '\Convo\Core\Intent\IIntentDriven');

	    $intents           =   [];
	    $entities          =   [];
        $numberOfValidIntents =   0;

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
			    $context = $service->findContext($catalog_name);

			    $this->_logger->debug('Got context ['.$context.']');

				$catalog = $context->getComponent();
				$data['interactionModel']['languageModel']['types'][] = $this->_buildCatalogEntity($entity->getName(), $catalog_name, $catalog, $vendorId);
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

            if ($numberOfSampleUtterances > 0 || $intent->isSystem()) {
                $numberOfValidIntents++;
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

	    if ($numberOfValidIntents === 0) {
            $data['interactionModel']['languageModel']['intents'][]    =   [
                "name" => "HelloWorld",
                "samples" => ["hello world"],
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

	    $export    =   new \Convo\Core\Util\SimpleFileResource(
	        $this->_serviceId.'-'.$this->getPlatformId().'.json', 'application/json', json_encode( $data, JSON_PRETTY_PRINT));

	    return $export;
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
	 * @param ICatalogSource $catalog
	 * @param string $vendorId
	 * @return array
	 * @throws \Exception
	 */
	private function _buildCatalogEntity($entityName, $catalogName, $catalog, $vendorId)
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
		$this->_logger->debug('Going to check if catalog ['.$catalogName.'] exists.');

		$config = $this->_convoServiceDataProvider->getServicePlatformConfig(
		    $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

		$dev_version = $this->_serviceReleaseManager->getDevelopmentAlias(
			$this->_user, $this->_serviceId, $this->getPlatformId()
		);

		$this->_logger->debug('Got development version alias ['.$dev_version.']');

		if (!isset($config[$this->getPlatformId()]['catalogs'][$catalogName][$dev_version]))
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

			$config[$this->getPlatformId()]['catalogs'][$catalogName][$dev_version] = [
				'time_created' => time(),
				'time_updated' => time(),
				'catalog_id' => $catalog_id
			];

			$version = $this->_amazonPublishingService->createCatalogVersion(
				$this->_user,
				$catalog_id,
				$this->_buildCatalogSourceUrl($catalogName, $dev_version),
				StrUtil::uuidV4()
			);

			$this->_logger->debug('Catalog version created successfully');

			$config[$this->getPlatformId()]['catalogs'][$catalogName][$dev_version]['time_updated'] = time();
			$config[$this->getPlatformId()]['catalogs'][$catalogName][$dev_version]['version'] = $version['lastUpdateRequest']['version'];
			$this->_convoServiceDataProvider->updateServicePlatformConfig(
				$this->_user, $this->_serviceId, $config
			);

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
		}
		else
		{
			$existing = $config[$this->getPlatformId()]['catalogs'][$catalogName][$dev_version];
			$v = $existing['version'];

			$this->_logger->debug('Catalog already exists ['.print_r($existing, true).']['.$catalog->getCatalogVersion().']');

			if ($v !== $catalog->getCatalogVersion())
			{
				$this->_logger->debug('Stored catalog version does not match actual. Going to update new version');

				$version = $this->_amazonPublishingService->createCatalogVersion(
					$this->_user,
					$existing['catalog_id'],
					$this->_buildCatalogSourceUrl($catalogName, $dev_version),
					StrUtil::uuidV4()
				);

				$current_ver = $version['lastUpdateRequest']['version'];

				$this->_logger->debug('Catalog version ['.$current_ver.'] created successfully');

				$config[$this->getPlatformId()]['catalogs'][$catalogName][$dev_version]['time_updated'] = time();
				$config[$this->getPlatformId()]['catalogs'][$catalogName][$dev_version]['version'] = $current_ver;
				$this->_convoServiceDataProvider->updateServicePlatformConfig(
					$this->_user, $this->_serviceId, $config
				);

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
        $platform_config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );
        $skill_id = $platform_config[$this->getPlatformId()]['app_id'];
        $mode = $platform_config[$this->getPlatformId()]['mode'] ?? 'manual';

        if ($mode === 'auto') {
            try {
                $delete_res = $this->_amazonPublishingService->deleteSkill($this->_user, $skill_id);
                $this->_logger->debug('Amazon skill ['.$skill_id.'] deleted successfully, response ['.print_r($delete_res, true).']');

                $report['success']['amazon']['skill'] = $delete_res;
            } catch (\Exception $e) {
                $this->_logger->error($e);
                $report['errors']['amazon'] = $e->getMessage();
            }

            if (isset($platform_config['amazon']['catalogs']))
            {
                $this->_logger->debug('Going to delete amazon catalogs');

                foreach ($platform_config['amazon']['catalogs'] as $catalog_name => $catalog_data)
                {
                    try {
                        $catalog_id = $catalog_data['catalog_id'];
                        $catalog_delete_res = $this->_amazonPublishingService->deleteCatalog($this->_user, $catalog_id);

                        $report['success']['amazon']['catalogs'][$catalog_name] = $catalog_delete_res;
                    } catch (\Exception $e) {
                        $this->_logger->error($e);
                        $report['errors']['amazon']['catalogs'][$catalog_name] = $e->getMessage();
                    }
                }
            }
        } else {
            $this->_logger->debug('Will not delete service due to manual mode selection.');
            $report['success']['amazon']['skill'] = "Skill with id [" . $skill_id . "] will not be deleted due to manual mode selection in the service platform configuration.";
        }
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
                $sslCertificate = $this->_mediaService->getMediaItem(
                    $this->_serviceId, $config['self_signed_certificate']
                    )->getContent();
                $this->_logger->debug("Printing self signed ssl certificate [" . $sslCertificate . "] when [" . $config['endpoint_ssl_certificate_type'] . "] is selected." );
                $this->_amazonPublishingService->uploadSelfSignedSslCertificateToSkill($owner, $skillId, $sslCertificate);
            }
        }
    }

    private function _manageAccountLinking($owner, $skillId, $stage, $amazonConfiguration) {
        if (isset($amazonConfiguration['enable_account_linking']) && isset($amazonConfiguration['account_linking_config'])) {
            if ($amazonConfiguration['enable_account_linking']) {
                $body = [
                    "accountLinkingRequest" => [
                        "skipOnEnablement" => $amazonConfiguration['account_linking_config']["skip_on_enablement"] ?? false,
                        "type" => "AUTH_CODE",
                        "authorizationUrl" => $amazonConfiguration['account_linking_config']["authorization_url"] ?? "",
                        "domains" => isset($amazonConfiguration['account_linking_config']["domains"]) ? explode(";", $amazonConfiguration['account_linking_config']["domains"]) : [],
                        "scopes" => isset($amazonConfiguration['account_linking_config']["scopes"]) ? explode(";", $amazonConfiguration['account_linking_config']["scopes"]) : [],
                        "accessTokenUrl" => $amazonConfiguration['account_linking_config']["access_token_url"] ?? "",
                        "clientId" => $amazonConfiguration['account_linking_config']["client_id"] ?? "",
                        "clientSecret" => $amazonConfiguration['account_linking_config']["client_secret"] ?? "",
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

    private function _getDownloadLink($serviceId, $mediaItem, $owner, $alternativeDownloadLink = '') {
        $iconUrl = $alternativeDownloadLink;
        try {
            if ($mediaItem !== '') {
                $parsedUrl = parse_url($mediaItem);
                if (is_array($parsedUrl) && count($parsedUrl) > 1) {
                    $iconUrl = $mediaItem;
                } else if (is_array($parsedUrl) && count($parsedUrl) === 1) {
                    $iconUrl = $this->_mediaService->getMediaUrl($serviceId, $mediaItem);
                }
            }
            $this->_amazonPublishingService->checkSkillIconAvailability($iconUrl, $owner);
        } catch (ClientExceptionInterface $e) {
            $this->_logger->warning("Could not fetch image with url " . [$iconUrl]);
            $this->_logger->warning("More info [" . $e->getMessage() . "]");
            $iconUrl = $alternativeDownloadLink;
        }

        return $iconUrl;
    }

    private function _sanitizeText($text) {
        return preg_replace('/[^a-zA-Z ]/', '', $text);
    }
}
