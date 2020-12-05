<?php declare(strict_types=1);

namespace Convo\Core\Util;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class JsonHeaderMiddleware implements \Psr\Http\Server\MiddlewareInterface
{	
	
    public function process( ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $response   =   $handler->handle( $request);
        
        if ( $response->hasHeader( 'Content-Type')) {
            return $response;
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}