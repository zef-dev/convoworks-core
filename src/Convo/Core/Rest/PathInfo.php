<?php declare(strict_types=1);

namespace Convo\Core\Rest;


class PathInfo
{	
	private $_data	=	array();
	private $_path;
	
	public function __construct( $path)
	{
		$this->_path	=	$path;
	}
	
	public function add( $key, $val)
	{
		$this->_data[$key] = $val;
	}
	
	public function get( $key)
	{
		if ( !isset( $this->_data[$key])) {
			throw new \Convo\Core\Rest\NotFoundException( 'Could not locate ['.$key.'] in ['.$this->_path.']');
		}
		
		return $this->_data[$key];
	}
	
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->_path.']['.json_encode( $this->_data).']';
	}
}