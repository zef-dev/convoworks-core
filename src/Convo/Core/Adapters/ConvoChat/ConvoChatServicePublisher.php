<?php declare(strict_types=1);

namespace Convo\Core\Adapters\ConvoChat;

class ConvoChatServicePublisher extends \Convo\Core\Publish\AbstractServicePublisher
{

    public function __construct( $logger, \Convo\Core\IAdminUser $user, $serviceId, $serviceDataProvider, $serviceReleaseManager)
	{
	    parent::__construct( $logger, $user, $serviceId, $serviceDataProvider, $serviceReleaseManager);
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
        // TODO: Implement delete() method.
    }
}
