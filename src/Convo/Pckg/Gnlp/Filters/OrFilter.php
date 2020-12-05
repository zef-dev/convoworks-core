<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Filters;

class OrFilter implements ITextFilter
{
	public static $DFAULTS	=	array(
			'collect_all' => false,
			'filters' => array(),
	);
	private $_collectAll;
	/**
	 * @var \Convo\Pckg\Gnlp\Filters\ITextFilter[]
	 */
	private $_filters	=	array();
	
	/**
	 * @var \Convo\Pckg\Gnlp\NlpFilterResult[]
	 */
	private $_results	=	array();
	
	/**
	 * @var \Convo\Pckg\Gnlp\NlpFilterResult
	 */
	private $_filterResult;
	
	public function __construct( $config)
	{
		$this->_filterResult	=	new \Convo\Pckg\Gnlp\NlpFilterResult();
		
		$config					=	array_merge( self::$DFAULTS, $config);
		
		foreach ( $config['filters'] as $filter) {
			/* @var $filter \Convo\Pckg\Gnlp\Filters\ITextFilter */
			$this->_filters[]	=	$filter;
		}
		
		$this->_collectAll	=	$config['collect_all'];
	}
	
	public function visitToken( \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $token)
	{
		foreach ( $this->_filters as $filter) {
			/* @var $filter \Convo\Pckg\Gnlp\Filters\ITextFilter */
			$filter->visitToken( $token);
			$sub_result		=	$filter->getFilterResult();
			if ( !$sub_result->isEmpty()) {
				$this->_filterResult->read( $sub_result);
				if ( !$this->_collectAll) {
					return;
				}
			}
		}
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