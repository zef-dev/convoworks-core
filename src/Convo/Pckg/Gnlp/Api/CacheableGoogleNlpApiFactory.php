<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Api;

class CacheableGoogleNlpApiFactory implements IGoogleNlpFactory
{
	/**
	 * @var \Psr\SimpleCache\CacheInterface
	 */
	private $_cache;
	
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;
	
	/**
	 * @var \Convo\Core\Util\IHttpFactory
	 */
	private $_httpFactory;
	
	public function __construct( \Psr\SimpleCache\CacheInterface $cache, $logger, $httpFactory)
	{
		$this->_cache		=	$cache;
		$this->_logger		=	$logger;
		$this->_httpFactory	=	$httpFactory;
	}
	
	public function getApi( $apiKey)
	{
		$nlpApi = new GoogleNlpApi( $apiKey, $this->_logger, $this->_httpFactory);
		
		return new CacheableGoogleNlpApi( $this->_cache, $nlpApi);
	}	
	
	// UTIL
	public function __toString()
	{
		return get_class( $this);
	}
}