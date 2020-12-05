<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Filters;

class NopRequestFilter implements \Convo\Core\Workflow\IRequestFilter
{
	/**
	 * @var \Convo\Core\Workflow\IRequestFilterResult
	 */
	private $_filterResult;

	/**
	 * @var \Convo\Core\ConvoServiceInstance
	 */
	private $_service;
	
	private $_id;
	private $_empty;
	
	public function __construct( $config)
	{
		$this->_filterResult	=	new \Convo\Core\Workflow\DefaultFilterResult();
		$this->_id				=	$config['_component_id'] ?? null;
		$this->_empty			=	$config['empty'] ?? 'empty';
	}
	
	public function getId()
	{
		return $this->_id;
	}
	
	private function _isMatch()
	{
	    if ( $this->_empty === 'match') {
	        return true;
	    }
	    
	    return false;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IRequestFilter::accepts()
	 */
	public function accepts( \Convo\Core\Workflow\IConvoRequest $request) {
		return $this->_isMatch();
	}

	public function filter( \Convo\Core\Workflow\IConvoRequest $request)
	{
	    $result    =  new \Convo\Core\Workflow\DefaultFilterResult();
	    if ( $this->_isMatch()) {
	        // DUMMY VALUE
	        $result->setSlotValue( get_class( $this), true);
	    }
	    return $result;
	}

	public function setService( \Convo\Core\ConvoServiceInstance $service)
	{
		$this->_service     =   $service;
	}

	public function getService()
	{
		return $this->_service;
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}