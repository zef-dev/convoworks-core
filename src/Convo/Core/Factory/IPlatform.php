<?php declare(strict_types=1);

namespace Convo\Core\Factory;

use Psr\Http\Server\RequestHandlerInterface;

interface IPlatform
{
	
    /**
     * @return string
     */
    public function getPlatformId();
 
	/**
	 * @return RequestHandlerInterface
	 */
    public function getPublicRestHandler();

}