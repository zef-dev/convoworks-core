<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Fbm;

use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Util\NotImplementedException;

class FacebookMessengerServicePublisher extends \Convo\Core\Publish\AbstractServicePublisher
{
    /**
     * @var \Convo\Core\Adapters\Fbm\FacebookMessengerApiFactory
     */
    private $_facebookMessengerApiFactory;

    public function __construct( $logger, \Convo\Core\IAdminUser $user, $serviceId, $facebookMessengerApiFactory, $serviceDataProvider, $serviceReleaseManager)
	{
	    parent::__construct( $logger, $user, $serviceId, $serviceDataProvider, $serviceReleaseManager);
	    $this->_facebookMessengerApiFactory = $facebookMessengerApiFactory;
	}

	public function getPlatformId()
	{
		return 'facebook_messenger';
	}

	public function export()
	{
	    throw new \Exception( 'Not supported yet');
	}

	public function enable()
	{
	    parent::enable();
	    $config            =   $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        if (isset( $config[$this->getPlatformId()])) {
            $this->_updateFacebookMessengerBot();
        } else {
            throw new \Exception("Missing platform config [" . $this->getPlatformId());
        }
	}

	public function propagate()
    {
        $config            =   $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        if (isset( $config[$this->getPlatformId()])) {
            $this->_updateFacebookMessengerBot();
            $this->_recordPropagation();
        } else {
            throw new \Exception("Missing platform config [" . $this->getPlatformId());
        }
    }

    public function getPropagateInfo()
    {
        $data              =   parent::getPropagateInfo();
        $config            =   $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        if ( !isset( $config[$this->getPlatformId()])) {
            $this->_logger->debug( 'No platform ['.$this->getPlatformId().'] config in service ['.$this->_serviceId.']. Exiting ... ');
            return $data;
        }

        $this->_logger->info(print_r($config, true) . " Accessing platform config");
        $platform_config   =   $config[$this->getPlatformId()];

        $this->_logger->debug( 'Got auto mode. Checking further ... ');

        if ( isset( $platform_config['page_access_token']) && !empty( $platform_config['page_access_token'])) {
            $data['allowed'] = true;
        }

        $workflow  =   $this->_convoServiceDataProvider->getServiceData( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        $meta      =   $this->_convoServiceDataProvider->getServiceMeta( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        if (isset($meta['release_mapping'][$this->getPlatformId()])) {
          $alias     =   $this->_serviceReleaseManager->getDevelopmentAlias( $this->_user, $this->_serviceId, $this->getPlatformId());
          $mapping   =   $meta['release_mapping'][$this->getPlatformId()][$alias];

          if ( !isset( $mapping['time_propagated']) || empty( $mapping['time_propagated'])) {
            $this->_logger->debug( 'Never propagated ');
            $data['available'] = true;
          } else {
            if ( $mapping['time_propagated'] < $platform_config['time_updated']) {
              $this->_logger->debug( 'Config changed');
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

    private function _updateFacebookMessengerBot() {
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        $config[$this->getPlatformId()]['webhook_build_status'] = IPlatformPublisher::SERVICE_PROPAGATION_STATUS_IN_PROGRESS;
        $this->_convoServiceDataProvider->updateServicePlatformConfig($this->_user, $this->_serviceId, $config);

        $facebookMessengerApi = $this->_facebookMessengerApiFactory->getApi($this->_user, $this->_serviceId, $this->_convoServiceDataProvider);
        $url = $this->_serviceReleaseManager->getWebhookUrl($this->_user, $this->_serviceId, $this->getPlatformId());
        $facebookMessengerApi->callSubscriptionsApi($url);
        $facebookMessengerApi->callSubscribedApps();
        $facebookMessengerApi->callMessengerProfileApi();
    }

    public function delete(array &$report)
    {
        throw new NotImplementedException('Deletion not yet implemented for ['.$this->getPlatformId().'] platform');
    }

    public function getStatus()
    {
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        $status = ['status' => IPlatformPublisher::SERVICE_PROPAGATION_STATUS_FINISHED];
        if (isset($config[$this->getPlatformId()]['webhook_build_status'])) {
            $status['status'] = $config[$this->getPlatformId()]['webhook_build_status'];
        }
        return $status;
    }
}
