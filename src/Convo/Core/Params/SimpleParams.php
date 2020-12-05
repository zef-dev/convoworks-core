<?php declare(strict_types=1);

namespace Convo\Core\Params;

/**
 * @author tole
 * @todo rename: InMemoryParaams
 */
class SimpleParams implements IServiceParams
{
	private $_params	=	array();
	
	public function getServiceParam( $name) {
		return $this->_params[$name] ?? null;
	}
	
	public function setServiceParam( $name, $value) {
		$this->_params[$name]	=	$value;
	}
	
	public function getData() {
		return $this->_params;
	}
	
	public function __toString()
	{
		return get_class( $this);
	}
}