<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Api;


class GoogleNlpFactory implements IGoogleNlpFactory
{
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;
	
	/**
	 * @var \Convo\Core\Util\IHttpFactory
	 */
	private $_httpFactory;
	
	public function __construct( $logger, $httpFctory)
	{
		$this->_logger		=	$logger;
		$this->_httpFactory	=	$httpFctory;
	}

	public function getApi( $apiKey)
	{
		if ( empty( $apiKey)) {
			throw new \Exception( 'Empty api key passed');
		}
		return new \Convo\Pckg\Gnlp\Api\GoogleNlpApi( $apiKey, $this->_logger, $this->_httpFactory);
	}
}