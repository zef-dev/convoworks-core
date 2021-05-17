<?php


namespace Convo\Core\Adapters\Viber;


use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Publish\PlatformPublishingHistory;
use Psr\Http\Client\ClientExceptionInterface;

class ViberServicePublisher extends \Convo\Core\Publish\AbstractServicePublisher
{
    /**
     * @var ViberApi
     */
    private $_viberApi;

    /**
     * @var PlatformPublishingHistory
     */
    private $_platformPublishingHistory;

    public function __construct($logger, \Convo\Core\IAdminUser $user, $serviceId, $viberApi, $serviceDataProvider, $serviceReleaseManager, $platformPublishingHistory)
    {
        parent::__construct($logger, $user, $serviceId, $serviceDataProvider, $serviceReleaseManager);
        $this->_viberApi                  = $viberApi;
        $this->_platformPublishingHistory = $platformPublishingHistory;
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
            $this->_platformPublishingHistory->storePropagationData($this->_serviceId, $this->getPlatformId(), $this->_preparePropagateData());
        } else {
            throw new \Exception("Missing platform config [" . $this->getPlatformId());
        }
    }

    public function propagate()
    {
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        if (isset( $config[$this->getPlatformId()])) {
            $this->_updateViberChatBot();
            $this->_platformPublishingHistory->storePropagationData($this->_serviceId, $this->getPlatformId(), $this->_preparePropagateData());
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
        $meta      =   $this->_convoServiceDataProvider->getServiceMeta( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        $changesCount = 0;

        if (isset($meta['release_mapping'][$this->getPlatformId()])) {
            $alias     =   $this->_serviceReleaseManager->getDevelopmentAlias( $this->_user, $this->_serviceId, $this->getPlatformId());
            $mapping   =   $meta['release_mapping'][$this->getPlatformId()][$alias];

            if ( !isset( $mapping['time_propagated']) || empty( $mapping['time_propagated'])) {
                $this->_logger->debug( 'Never propagated ');
                $data['available'] = true;
            } else {
                if ( $mapping['time_propagated'] < $platform_config['time_updated']) {
                    $this->_logger->debug( 'Config changed');
                    $configChanged = $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
                        $this->_serviceId,
                        $this->getPlatformId(),
                        PlatformPublishingHistory::VIBER_EVENT_TYPES,
                        $platform_config['event_types']
                    ) || $this->_platformPublishingHistory->hasPropertyChangedSinceLastPropagation(
                            $this->_serviceId,
                            $this->getPlatformId(),
                            PlatformPublishingHistory::VIBER_AUTH_TOKEN,
                            $platform_config['auth_token']
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

                if ($changesCount > 0) {
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
            $this->_platformPublishingHistory->removeSoredPropagationData($this->_serviceId, $this->getPlatformId());
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

    private function _preparePropagateData() {
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        return [
            PlatformPublishingHistory::VIBER_AUTH_TOKEN => $config[$this->getPlatformId()]['auth_token'],
            PlatformPublishingHistory::VIBER_EVENT_TYPES => $config[$this->getPlatformId()]['event_types']
        ];
    }
}
