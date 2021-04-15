<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Google\Common;

use Convo\Core\Adapters\Google\Gactions\ActionsCommandRequest;
use Convo\Core\IAdminUser;
use Convo\Core\Publish\AbstractServicePublisher;
use Convo\Core\Util\NotImplementedException;

class GactionsPublisher extends AbstractServicePublisher
{

    public function __construct( $logger, IAdminUser $user, $serviceId, $serviceDataProvider, $serviceReleaseManager)
	{
	    parent::__construct( $logger, $user, $serviceId, $serviceDataProvider, $serviceReleaseManager);
	}

	public function getPlatformId()
	{
		return ActionsCommandRequest::PLATFORM_ID;
	}

	public function export()
	{
	    throw new \Exception( 'Not supported');
	}

	public function delete(array &$report)
    {
        throw new NotImplementedException('Deletion not yet implemented for ['.$this->getPlatformId().'] platform');
    }
}
