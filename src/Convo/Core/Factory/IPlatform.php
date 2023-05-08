<?php declare(strict_types=1);

namespace Convo\Core\Factory;

use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\IAdminUser;

interface IPlatform
{
	
    /**
     * @return string
     */
    public function getPlatformId();

    /**
     * @param IAdminUser $user
     * @param string $serviceId
	 * @return IPlatformPublisher
	 */
    public function getPlatformPublisher( IAdminUser $user, $serviceId);

}