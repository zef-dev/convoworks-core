<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Filters;


use Convo\Pckg\Gnlp\NlpFilterResult;

abstract class AbstractFilter implements ITextFilter, \Psr\Log\LoggerAwareInterface
{
	/**
	 * @var NlpFilterResult
	 */
	protected $_filterResult;
	
	/**
	 * Logger
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;
	
	public function __construct()
	{
		$this->_filterResult	=	new NlpFilterResult();
		$this->_logger			=	new \Psr\Log\NullLogger();
	}
	
	public function setLogger( \Psr\Log\LoggerInterface $logger)
	{
		$this->_logger	=	$logger;
	}
	
	public function getFilterResult()
	{
		return $this->_filterResult;
	}
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}