<?php declare(strict_types=1);

namespace Convo\Core\Util;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class LogRequestMiddleware implements \Psr\Http\Server\MiddlewareInterface
{	
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;
	
	public function __construct( \Psr\Log\LoggerInterface $logger)
	{
		$this->_logger	=	$logger;
	}
	
	public function process( ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$this->_logger->debug( '============================================================');
		if (isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST'])) {
			$this->_logger->debug( $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		}
		
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$this->_logger->debug( 'Content-Type: '.$_SERVER['CONTENT_TYPE']);
		}
		
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$this->_logger->debug( 'User-Agent: '.$_SERVER['HTTP_USER_AGENT']);
		}
		
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->_logger->debug( 'IP: '.$_SERVER['HTTP_X_FORWARDED_FOR']);
		}
		
		else if (isset($_SERVER['REMOTE_ADDR'])) {
			$this->_logger->debug( 'IP: '.$_SERVER['REMOTE_ADDR']);
		}
		
		if (isset($_SERVER['REQUEST_METHOD'])) {
			$this->_logger->debug( 'Method: '.$_SERVER['REQUEST_METHOD']);
		}
		
		$this->_logger->debug( '============================================================');
		return $handler->handle( $request);
	}
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}