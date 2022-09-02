<?php declare(strict_types=1);

namespace Convo\Core\Factory;

use Psr\Http\Server\RequestHandlerInterface;

interface IPlatformProvider
{
	
	/**
	 * @return RequestHandlerInterface
	 */
    public function getPublicRestHandler();

}