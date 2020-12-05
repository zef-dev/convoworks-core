<?php declare(strict_types=1);

namespace Convo\Core\Factory;

class DefaultComponentFactory implements \Convo\Core\Factory\IComponentFactory
{
    private $_componentData;
    
    public function __construct( $componentData)
    {    
        $this->_componentData = $componentData;
    }
    
    public function createComponent( $properties, $service)
    {    
	   return new $this->_componentData['class']( $properties, $service);
    }
    public function __toString()
    {
        return get_class( $this).'['.json_encode( $this->_componentData).']';
    }
}