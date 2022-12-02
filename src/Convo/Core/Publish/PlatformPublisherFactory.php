<?php declare(strict_types=1);

namespace Convo\Core\Publish;

use Convo\Core\IAdminUser;
use Convo\Core\Rest\OwnerNotSpecifiedException;
use Convo\Core\Factory\IPlatformProvider;

class PlatformPublisherFactory
{
	/**
	 * @var string
	 */

	private $_publicRestBaseUrl;
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
	 * @var \Convo\Core\Params\IServiceParamsFactory
	 */
	private $_convoServiceParamsFactory;

	/**
	 * @var \Convo\Core\Media\IServiceMediaManager
	 */
	private $_mediaService;

	/**
	 * @var \Convo\Core\Adapters\Alexa\AmazonPublishingService
	 */
	private $_amazonPublishingService;

	/**
	 * @var \Convo\Core\Adapters\Dialogflow\DialogflowApiFactory
	 */
	private $_dialogflowApiFactory;

    /**
     * @var \Convo\Core\Adapters\Fbm\FacebookMessengerApiFactory
     */
    private $_facebookMessengerApiFactory;

    /**
     * @var \Convo\Core\Adapters\Viber\ViberApi
     */
    private $_viberApi;

	/**
	 * @var \Convo\Core\Factory\PackageProviderFactory
	 */
	private $_packageProviderFactory;

	/**
	 * @var \Convo\Core\IAdminUserDataProvider
	 */
	private $_adminUserDataProvider;

	/**
	 * @var \Convo\Core\Publish\ServiceReleaseManager
	 */
	private $_serviceReleaseManager;

    /**
     * @var PlatformPublishingHistory
     */
    private $_platformPublishingHistory;

	public function __construct(
        $publicRestBaseUrl,
        $logger,
        $serviceFactory,
        $serviceDataProvider,
        $serviceParamsFactory,
        $mediaService,
        $amazonPublishingService,
        $dialogflowApiFactory,
        $facebookMessengerApiFactory,
        $viberApi,
        $packageProviderFactory,
        $adminUserDataProvider,
        $serviceReleaseManager,
        $platformPublishingHistory
	)
	{
		$this->_publicRestBaseUrl			=	$publicRestBaseUrl;
		$this->_logger						=	$logger;
		$this->_convoServiceFactory			= 	$serviceFactory;
		$this->_convoServiceDataProvider	= 	$serviceDataProvider;
		$this->_convoServiceParamsFactory	= 	$serviceParamsFactory;
		$this->_mediaService				=	$mediaService;
		$this->_amazonPublishingService		=	$amazonPublishingService;
		$this->_dialogflowApiFactory		=	$dialogflowApiFactory;
		$this->_facebookMessengerApiFactory =	$facebookMessengerApiFactory;
		$this->_viberApi                    =	$viberApi;
		$this->_packageProviderFactory		=	$packageProviderFactory;
		$this->_adminUserDataProvider		=	$adminUserDataProvider;
		$this->_serviceReleaseManager       =	$serviceReleaseManager;
        $this->_platformPublishingHistory   =	$platformPublishingHistory;
	}

	/**
	 * @param \Convo\Core\IAdminUser $user
	 * @param string $serviceId
	 * @param string $platformId
	 * @return \Convo\Core\Publish\IPlatformPublisher
	 * @throws \Convo\Core\ComponentNotFoundException
	 */
	public function getPublisher( \Convo\Core\IAdminUser $user, $serviceId, $platformId)
	{
		$this->_logger->debug( 'Getting platform ['.$platformId.'] publisher');
		$owner = $this->_getServiceOwner($user, $serviceId);

		if ( $platformId === \Convo\Core\Adapters\Alexa\AmazonCommandRequest::PLATFORM_ID) {
			return new \Convo\Core\Adapters\Alexa\AlexaSkillPublisher(
				$this->_publicRestBaseUrl,
				$this->_logger,
                $owner,
				$serviceId,
				$this->_convoServiceFactory,
				$this->_convoServiceDataProvider,
				$this->_convoServiceParamsFactory,
				$this->_amazonPublishingService,
				$this->_packageProviderFactory,
				$this->_adminUserDataProvider,
			    $this->_serviceReleaseManager,
                $this->_mediaService,
                $this->_platformPublishingHistory
			);
		}

		if ($platformId === \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandRequest::PLATFORM_ID) {
			return new \Convo\Core\Adapters\Dialogflow\DialogflowPublisher(
				$this->_logger,
                $owner,
				$serviceId,
				$this->_convoServiceFactory,
				$this->_convoServiceDataProvider,
				$this->_convoServiceParamsFactory,
				$this->_packageProviderFactory,
				$this->_dialogflowApiFactory,
				$this->_mediaService,
			    $this->_serviceReleaseManager,
			    $this->_platformPublishingHistory
			);
		}

        if ($platformId === 'dialogflow_es') {
            return new \Convo\Core\Adapters\DialogflowEs\DialogflowEsPublisher(
                $this->_logger,
                $owner,
                $serviceId,
                $this->_convoServiceFactory,
                $this->_convoServiceDataProvider,
                $this->_convoServiceParamsFactory,
                $this->_packageProviderFactory,
                $this->_dialogflowApiFactory,
                $this->_serviceReleaseManager,
                $this->_platformPublishingHistory
            );
        }

		if ( $platformId === \Convo\Core\Adapters\Google\Gactions\ActionsCommandRequest::PLATFORM_ID) {
			return new \Convo\Core\Adapters\Google\Common\GactionsPublisher(
			    $this->_logger, $owner, $serviceId, $this->_convoServiceDataProvider, $this->_serviceReleaseManager);
		}

        if ( $platformId === \Convo\Core\Adapters\Fbm\FacebookMessengerCommandRequest::PLATFORM_ID) {
          return new \Convo\Core\Adapters\Fbm\FacebookMessengerServicePublisher(
            $this->_logger, $owner, $serviceId, $this->_facebookMessengerApiFactory, $this->_convoServiceDataProvider, $this->_serviceReleaseManager, $this->_platformPublishingHistory);
        }

        if ( $platformId === \Convo\Core\Adapters\Viber\ViberCommandRequest::PLATFORM_ID) {
            return new \Convo\Core\Adapters\Viber\ViberServicePublisher(
                $this->_logger, $owner, $serviceId, $this->_viberApi, $this->_convoServiceDataProvider, $this->_serviceReleaseManager, $this->_platformPublishingHistory);
        }

		if ( $platformId === \Convo\Core\Adapters\ConvoChat\DefaultTextCommandRequest::PLATFORM_ID) {
			return new \Convo\Core\Adapters\ConvoChat\ConvoChatServicePublisher(
			    $this->_logger, $owner, $serviceId, $this->_convoServiceDataProvider, $this->_serviceReleaseManager);
		}
		
		if ( strpos( $platformId, '.') !== false) {
		    $parts = explode( '.', $platformId, 2);
		    $package_id = $parts[0];
		    $platform_id = $parts[1];
		    $provider = $this->_packageProviderFactory->getProviderByServiceId( $user, $serviceId);
		    $package = $provider->findPackageById( $package_id);
		    if ( $package instanceof IPlatformProvider) {
		        /* @var IPlatformProvider $package */
		        $platform = $package->getPlatform( $platform_id);
		        return $platform->getPlatformPublisher( $user, $serviceId);
		    }
		}

		throw new \Convo\Core\ComponentNotFoundException( 'Could not find publiher for platform ['.$platformId.']');
	}

    /**
     * @param $user IAdminUser
     * @param $serviceId
     * @return IAdminUser
     * @throws \Convo\Core\DataItemNotFoundException
     * @throws OwnerNotSpecifiedException
     */
	private function _getServiceOwner($user, $serviceId) {
        $owner = null;
        $this->_logger->debug( 'Current logged in user ['.$user->getUsername().']['.$user->getEmail().']');

        $meta = $this->_convoServiceDataProvider->getServiceMeta($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        if (!empty($meta["owner"])) {
            $owner = $this->_adminUserDataProvider->findUser($meta["owner"]);
            $this->_logger->debug(
                'Going to return owner ['.$owner->getUsername().']['.$owner->getEmail().'] of the service [' . $serviceId . ']'
            );
        } else {
            throw new OwnerNotSpecifiedException("Please specify an owner for the service [" . $serviceId . "]");
        }

	    return $owner;
    }


	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
