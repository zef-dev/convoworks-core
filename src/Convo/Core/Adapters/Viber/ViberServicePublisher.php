<?php


namespace Convo\Core\Adapters\Viber;


use Convo\Core\Publish\IPlatformPublisher;
use Psr\Http\Client\ClientExceptionInterface;

class ViberServicePublisher extends \Convo\Core\Publish\AbstractServicePublisher
{
    /**
     * @var ViberApi
     */
    private $_viberApi;
    public function __construct($logger, \Convo\Core\IAdminUser $user, $serviceId, $viberApi, $serviceDataProvider, $serviceReleaseManager)
    {
        parent::__construct($logger, $user, $serviceId, $serviceDataProvider, $serviceReleaseManager);
        $this->_viberApi = $viberApi;
    }

    /**
     * @inheritDoc
     */
    public function getPlatformId()
    {
        return 'viber';
    }

    /**
     * @inheritDoc
     */
    public function export()
    {
        throw new \Exception( 'Not supported yet');
    }

    public function enable()
    {
        parent::enable();
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        if (isset( $config[$this->getPlatformId()])) {
            $this->_updateViberChatBot();
        } else {
            throw new \Exception("Missing platform config [" . $this->getPlatformId());
        }
    }

    public function propagate()
    {
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        if (isset( $config[$this->getPlatformId()])) {
            $this->_updateViberChatBot();
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

        if ( isset( $platform_config['auth_token']) && !empty( $platform_config['auth_token'])) {
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

    private function _updateViberChatBot() {
        $config            =   $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        $config[$this->getPlatformId()]['webhook_build_status'] = IPlatformPublisher::SERVICE_PROPAGATION_STATUS_IN_PROGRESS;
        $this->_convoServiceDataProvider->updateServicePlatformConfig($this->_user, $this->_serviceId, $config);

        $url = $this->_serviceReleaseManager->getWebhookUrl($this->_user, $this->_serviceId, $this->getPlatformId());
        $this->_viberApi->setupViberApi($this->_user, $this->_serviceId, $config);
        $this->_viberApi->callSetupWebhook($url);
    }

    public function delete(array &$report)
    {
        try {
            $config            =   $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
            $this->_viberApi->setupViberApi($this->_user, $this->_serviceId, $config);
            $this->_viberApi->removeWebhook();
            $report['success'][$this->getPlatformId()]['viber_bot'] = "Viber bot has successfully removed the webhook.";
        } catch (ClientExceptionInterface $e) {
            $this->_logger->warning($e);
            $report['errors'][$this->getPlatformId()]['viber_bot'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->_logger->warning($e);
            $report['errors'][$this->getPlatformId()]['viber_bot'] = $e->getMessage();
        }
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
