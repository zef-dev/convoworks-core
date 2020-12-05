<?php declare(strict_types=1);

namespace Convo\Core\Params;


/**
 * @author tole
 */
class ComponentParamsScope extends RequestParamsScope
{
	
	/**
	 * @var \Convo\Core\Workflow\IBasicServiceComponent
	 */
	private $_component;
	
	public function __construct( $component, $request, $scopeType) {
		parent::__construct($request, $scopeType, IServiceParamsScope::LEVEL_TYPE_COMPONENT);
		$this->_component	=	$component;
	}
	
	public function getComponent() {
		return $this->_component;
	}
	
	public function getKey() 
	{
		$key	=	parent::getKey();
		$key	.=	'_';
		$key	.=	$this->_component->getId();
		
		return $key;
	}
	
	
	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_component.']';
	}

}