<?php declare(strict_types=1);

namespace Convo\Core\Util;

use Convo\Core\Events\ConvoServiceConversationRequestEvent;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class LogRequestMiddleware implements \Psr\Http\Server\MiddlewareInterface
{
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

    /*
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    private $_eventDispatcher;

	public function __construct( \Psr\Log\LoggerInterface $logger, EventDispatcher $eventDispatcher)
	{
		$this->_logger	=	$logger;
		$this->_eventDispatcher	=	$eventDispatcher;
	}

	public function process( ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
	    $start = microtime( true);

        /*$this->_eventDispatcher->addListener(ConvoRequestEvent::NAME, function (ConvoRequestEvent $event) {
            $convoRequestBody = $event->getConvoRequest()->getPlatformData();
            $convoResponseBody = $event->getConvoResponse()->getPlatformResponse();
            $this->_logger->info('Triggered event [' . ConvoRequestEvent::NAME . ']');
            $this->_logger->info('Platform request [' . json_encode($convoRequestBody, JSON_PRETTY_PRINT));
            $this->_logger->info('Platform response [' . json_encode($convoResponseBody, JSON_PRETTY_PRINT));
        });
        $numberOfEventListeners = count($this->_eventDispatcher->getListeners());
        $this->_logger->info("Number of Event Listeners [".$numberOfEventListeners.']');*/

		$this->_logger->info( '============================================================');
		if (isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST'])) {
			$this->_logger->info( $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
		}

		if (isset($_SERVER['CONTENT_TYPE'])) {
			$this->_logger->info( 'Content-Type: '.$_SERVER['CONTENT_TYPE']);
		}

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$this->_logger->info( 'User-Agent: '.$_SERVER['HTTP_USER_AGENT']);
		}

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->_logger->info( 'IP: '.$_SERVER['HTTP_X_FORWARDED_FOR']);
		}

		else if (isset($_SERVER['REMOTE_ADDR'])) {
			$this->_logger->info( 'IP: '.$_SERVER['REMOTE_ADDR']);
		}

		if (isset($_SERVER['REQUEST_METHOD'])) {
			$this->_logger->info( 'Method: '.$_SERVER['REQUEST_METHOD']);
		}

		$this->_logger->info( '============================================================');
		$response =   $handler->handle( $request);

		$time_elapsed_us = microtime( true) - $start;
		$this->_logger->info( 'Returning HTTP ['.$response->getStatusCode().'] in ' . ($time_elapsed_us * 1000) . ' ms');

		return $response;
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
