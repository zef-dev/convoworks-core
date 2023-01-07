<?php declare(strict_types=1);

namespace Convo\Core\Util;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @author Tole
 * 
 * Simple PSR-15 REST application with middleware dispatcher support.
 * Routing is not part of it and you have to determine route by yourself and pass correct handler to the app.
 */
class RestApp implements RequestHandlerInterface
{	
	
	/**
	 * @var \Psr\Container\ContainerInterface
	 */
	private $_container;
	
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;
	
	/**
	 * @var MiddlewareInterface[]
	 */
	private $_middlewares  =   [];
	
	/**
	 * @var MiddlewareInterface[]
	 */
	private $_originalMiddlewares  =   [];

	/**
	 * @var RequestHandlerInterface
	 */
	private $_defaultHandler;
	
	public function __construct( \Psr\Log\LoggerInterface $logger, $container, $defaultHandler, $middlewares)
	{
        $this->_logger          =   $logger;
        $this->_container       =   $container;
        $this->_defaultHandler  =   $defaultHandler;
        $this->_middlewares     =   $middlewares;
        $this->_originalMiddlewares     =   $middlewares;
	}
	
	public function reset()
	{
	    $this->_middlewares = $this->_originalMiddlewares;
	}
	
	/**
	 * Helper method to dump response.
	 * @param ResponseInterface $response
	 */
	public function writeResponse( ResponseInterface $response) {
	    http_response_code( $response->getStatusCode());
	    foreach ( $response->getHeaders() as $name=>$values) {
	        foreach ( $values as $value) {
	            header( sprintf( '%s: %s', $name, $value), false);
	        }
	    }
	    echo $response->getBody()->getContents();
	}
	
	public function handle( ServerRequestInterface $request): ResponseInterface
	{
	    if ( !empty( $this->_middlewares)) {
	        $next  =   array_shift( $this->_middlewares); 
	        return $next->process( $request, $this);
	    }
	    
	    $this->_logger->info( 'Running actual handler ['.get_class( $this->_defaultHandler).']');
	    
	    return $this->_defaultHandler->handle( $request);
	}
	
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}


}