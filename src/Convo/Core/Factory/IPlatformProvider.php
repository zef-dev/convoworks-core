<?php declare(strict_types=1);

namespace Convo\Core\Factory;

use Convo\Core\ComponentNotFoundException;

interface IPlatformProvider
{
	
    /**
     * @return IPlatform
     * @throws ComponentNotFoundException
     */
    public function getPlatform( $platformId);

}