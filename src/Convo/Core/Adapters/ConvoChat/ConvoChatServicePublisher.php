<?php declare(strict_types=1);

namespace Convo\Core\Adapters\ConvoChat;

use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Util\NotImplementedException;

class ConvoChatServicePublisher extends \Convo\Core\Publish\AbstractServicePublisher
{

    public function __construct( $logger, \Convo\Core\IAdminUser $user, $serviceId, $serviceDataProvider, $serviceReleaseManager, $platformPublisherFactory)
	{
	    parent::__construct( $logger, $user, $serviceId, $serviceDataProvider, $serviceReleaseManager);
        $this->_platformPublisherFactory = $platformPublisherFactory;
	}

	public function getPlatformId()
	{
		return DefaultTextCommandRequest::PLATFORM_ID;
	}

	public function export()
	{
	    throw new \Exception( 'Not supported');
	}

	public function enable()
	{
	    $this->_checkEnabled();

	    $this->_serviceReleaseManager->initDevelopmentRelease( $this->_user, $this->_serviceId, $this->getPlatformId(), 'b');
	}

	public function delete(array &$report)
    {
        throw new NotImplementedException('Deletion not yet implemented for ['.$this->getPlatformId().'] platform');
    }

    public function getStatus()
    {
        return ['status' => IPlatformPublisher::SERVICE_PROPAGATION_STATUS_FINISHED];
    }

    public function createRelease($platformId, $targetReleaseType, $targetReleaseStage, $alias, $versionId = null)
    {
        $result = [];
        $delegateNlp = $this->_convoServiceDataProvider->getServicePlatformConfig($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP)[$platformId]['delegateNlp'] ?? '';

        if (!empty($delegateNlp)) {
            $publisher = $this->_platformPublisherFactory->getPublisher($this->_user, $this->_serviceId, $delegateNlp);
            $result = $publisher->createRelease($platformId, $targetReleaseType, $targetReleaseStage, $alias, $versionId);
        }

        return $result;
    }

    public function createVersionTag($platformId, $versionTagId = null)
    {
        $result = [];
        $delegateNlp = $this->_convoServiceDataProvider->getServicePlatformConfig($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP)[$platformId]['delegateNlp'] ?? '';

        if (!empty($delegateNlp)) {
            $publisher = $this->_platformPublisherFactory->getPublisher($this->_user, $this->_serviceId, $delegateNlp);
            $result = $publisher->createVersionTag($platformId, $versionTagId);
        }

        return $result;
    }

    public function importToDevelop($platformId, $fromAlias, $toAlias, $versionId = null, $versionTag = null)
    {
        $result = [];
        $delegateNlp = $this->_convoServiceDataProvider->getServicePlatformConfig($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP)[$platformId]['delegateNlp'] ?? '';

        if (!empty($delegateNlp)) {
            $publisher = $this->_platformPublisherFactory->getPublisher($this->_user, $this->_serviceId, $delegateNlp);
            $result = $publisher->importToDevelop($platformId, $fromAlias, $toAlias, $versionId, $versionTag);
        }

        return $result;
    }

    public function importToRelease($platformId, $targetReleaseType, $targetReleaseStage, $alias, $versionId = null, $nextVersionId = null)
    {
        $result = [];
        $delegateNlp = $this->_convoServiceDataProvider->getServicePlatformConfig($this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP)[$platformId]['delegateNlp'] ?? '';

        if (!empty($delegateNlp)) {
            $publisher = $this->_platformPublisherFactory->getPublisher($this->_user, $this->_serviceId, $delegateNlp);
            $result = $publisher->importToRelease($platformId, $targetReleaseType, $targetReleaseStage, $alias, $versionId, $nextVersionId);
        }

        return $result;
    }
}
