<?php declare(strict_types=1);

namespace Convo\Core\Factory;

class ComponentDefinition
{
	private $_namespace;
	private $_type;
	private $_name;
	private $_description;
	private $_componentProperties;
	
	public function __construct( $namespace, $type, $name, $desc, $props) 
	{
		$this->_namespace			=	$namespace;
		$this->_type				=	$type;
		$this->_name				=	$name;
		$this->_description			=	$desc;
		$this->_componentProperties	=	$props;
	}
	
	public function marshalValue( $property, $value)
	{
		$type	=	isset( $this->_componentProperties[$property]['valueType']) ? $this->_componentProperties[$property]['valueType'] : 'string';
		if ( $type == 'int') {
			return intval( $value);
		} else if ( $type == 'float') {
			return floatval( $value);
		} else if ( $type == 'string') {
			return strval( $value);
		}
		
		throw new \Exception( 'Unsupported property ['.$property.'] value type ['.$type.']');
	}
	
	public function getType()
	{
		return $this->_type;
	}
	
	public function getComponentProperties()
	{
		return $this->_componentProperties;
	}
	
	public function getProperty( $name)
	{
		if ( !isset( $this->_componentProperties[$name])) {
			throw new \Convo\Core\ComponentNotFoundException( 'Property ['.$name.'] not found in definition ['.$this.']');
		}
		return $this->_componentProperties[$name];
	}
	
	public function getDefaultProperties()
	{
		$props	=	array();
		
		foreach ( $this->_componentProperties as $key=>$val)
		{
			if ( strpos( $key, '_') === 0) {
				continue;
			}
			$props[$key]	=	isset( $val['defaultValue']) ? $val['defaultValue'] : null;
		}
		
		return $props;
	}
	
	public function isPropertyDefined( $property)
	{
		return isset( $this->_componentProperties[$property]);
	}
	
	public function getDefaultValue( $property)
	{
		if ( $this->isPropertyDefined( $property)) {
			return $this->_componentProperties[$property]['defaultValue'];
		}	
		
		throw new \Convo\Core\ComponentNotFoundException( 'Property ['.$property.'] not found');
	}
	
	public function getRow() 
	{
		$row	=	array();
		
		$row['namespace']				=	$this->_namespace;
		$row['type']					=	$this->_type;
		$row['name']					=	$this->_name;
		$row['description']				=	$this->_description;
		$row['component_properties']	=	$this->_componentProperties;
		
		$row['_interfaces']             =   array_values( class_implements( $this->_type));
		
		return $row;
	}

    public function isAlias( $class)
    {
        if (isset($this->_componentProperties['_class_aliases'])) {
            foreach ($this->_componentProperties['_class_aliases'] as $classAlias) {
                if ($classAlias === $class) {
                    return true;
                }
            }
        }

        return false;
    }
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->_namespace.']['.$this->_type.']['.$this->_name.']';
	}
}