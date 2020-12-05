<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Filters;


use Convo\Pckg\Gnlp\NlpFilterResult;

class AndFilter implements ITextFilter
{
	public static $DFAULTS	=	array(
			'filters' => array(),
	);
	
	/**
	 * @var ITextFilter[]
	 */
	private $_filters		=	array();
	
	private $_results	=	array();
	
	/**
	 * @var NlpFilterResult
	 */
	private $_filterResult;
	
	public function __construct( $config)
	{
		$this->_filterResult	=	new NlpFilterResult();
		
		$config	=	array_merge( self::$DFAULTS, $config);
		
		foreach ( $config['filters'] as $filter) {
			/* @var \Convo\Pckg\Gnlp\Filters\ITextFilter $filter */
			$this->_filters[]	=	$filter;
		}
	}
	
	public function visitToken( \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $token)
	{
		for ( $i=0; $i<count( $this->_filters); $i++) 
		{
			/* @var \Convo\Pckg\Gnlp\Filters\ITextFilter $filter */
			/* @var \Convo\Pckg\Gnlp\NlpFilterResult $sub_result */
			
			$filter			=	$this->_filters[$i];
			$filter->visitToken( $token);
			
			if ( !isset( $this->_results[$i])) {
				$this->_results[$i]	=	new NlpFilterResult();
			}
			
			$sub_result		=	$this->_results[$i];
			$sub_result->read( $filter->getFilterResult());
		}
		
		foreach ( $this->_results as $filter_result) {
			/* @var \Convo\Pckg\Gnlp\NlpFilterResult $filter_result */
			if ( $filter_result->isEmpty()) {
				return ;
			}

			$this->_filterResult->read( $filter_result);
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