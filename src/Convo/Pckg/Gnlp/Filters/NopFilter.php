<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Filters;


class NopFilter implements ITextFilter
{
	/**
	 * @var \Convo\Pckg\Gnlp\NlpFilterResult
	 */
	private $_filterResult;
	
	public function __construct()
	{
		$this->_filterResult	=	new \Convo\Pckg\Gnlp\NlpFilterResult();
	}
	
	
	public function visitToken( \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $token)
	{
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