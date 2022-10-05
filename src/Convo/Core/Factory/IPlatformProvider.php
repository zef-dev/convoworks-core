<?php declare(strict_types=1);

namespace Convo\Core\Factory;

use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\ComponentNotFoundException;

interface IPlatformProvider
{
	
	/**
	 * @return RequestHandlerInterface
	 * @deprecated
	 */
    public function getPublicRestHandler();

    /**
     * @return IPlatform
     * @throws ComponentNotFoundException
     */
    public function getPlatform( $platformId);

}