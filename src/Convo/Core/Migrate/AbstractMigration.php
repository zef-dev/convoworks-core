<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

abstract class AbstractMigration implements \Psr\Log\LoggerAwareInterface
{
	
	/**
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;
	
	public function __construct()
	{
		$this->_logger			=	new \Psr\Log\NullLogger();
	}
	
	public function setLogger( \Psr\Log\LoggerInterface $logger)
	{
		$this->_logger	=	$logger;
	}

	public abstract function getVersion();
	
	public function migrate( $serviceData)
	{
		$serviceData	=	$this->_migrateService( $serviceData);
		$serviceData	=	$this->_migrateAssocArray( $serviceData);
		return $serviceData;
	}
	
	public function migrateConfig( $config)
	{
	    return $config;
	}

	public function migrateMeta( $meta)
	{
	    return $meta;
	}
	
	protected function _migrateComponent( $componentData) {
		return $componentData;
	}
	
	protected function _migrateService( $serviceData)
	{
		$serviceData['convo_service_version']	=	$this->getVersion();
		return $serviceData;
	}
	
	protected function _migrateAssocArray( $data)
	{
		foreach ( $data as $key=>$val) 
		{
			if ( $this->_isIndexedArray( $val)) 
			{
				// indexed
				$data[$key]	=	$this->_migrateCollection( $val);
			} 
			else if ( $this->_isAssocArray( $val)) 
			{
				if ( $this->_isComponent( $val)) {
					$val	=	$this->_migrateComponent( $val);
				}
				// go in depth
				$data[$key]	=	$this->_migrateAssocArray( $val);
			} 
			else 
			{
				// primitive, skip
			}
		}
		
		return $data;
	}
	
	protected function _migrateCollection( $array)
	{ 
		foreach ( $array as $i=>$val) 
		{
			if ( $this->_isIndexedArray( $val))
			{
				// indexed
				$array[$i]	=	$this->_migrateCollection( $val);
			}
			else if ( $this->_isAssocArray( $val))
			{
				if ( $this->_isComponent( $val)) {
					$val	=	$this->_migrateComponent( $val);
				}
				// go in depth
				$array[$i]	=	$this->_migrateAssocArray( $val);
			}
			else
			{
				// primitive, skip
			}
		}
		return $array;
	}
	
	private function _isComponent( $item)
	{ 
		if ( !is_array( $item)) {
			return false;
		}
		
		if ( empty( $item)) {
			return false;
		}
		
		if ( !isset( $item['class'])) {
			return false;
		}
		
		if ( !isset( $item['properties'])) {
			return false;
		}
		
		return true;
	}
	
	private function _isIndexedArray( $item)
	{ 
		if ( !is_array( $item)) {
			return false;
		}
		
		if ( empty( $item)) {
			return false;
		}
		
		if ( key( $item) === 0) {
			return true;
		}
		
		return false;
	}
	
	private function _isAssocArray( $item)
	{ 
		if ( !is_array( $item)) {
			return false;
		}
		
		if ( empty( $item)) {
			return false;
		}
		
		if ( is_string( key( $item))) {
			return true;
		}
		
		return false;
	}
	
	protected function _getTrueChildren( $childComponent) {
	    if ( $childComponent['class'] === '\\Convo\\Pckg\\Core\\Elements\\ElementCollection') {
	        if ( empty( $childComponent['properties']['elements'])) {
	            return [];
	        }
	        return $childComponent['properties']['elements'];
	    }
	    return [$childComponent];
	}
	
	public function __toString()
	{
		return get_class( $this).'['.$this->getVersion().']';
	}
}