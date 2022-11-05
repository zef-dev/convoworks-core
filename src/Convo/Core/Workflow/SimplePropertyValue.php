<?php

namespace Convo\Core\Workflow;

/**
 * @author Tole
 * Enables easier property evaluation. 
 */
class SimplePropertyValue implements IPropertyValue
{
    /**
     * @var string
     */
    private $_name;
    
    /**
     * @var array
     */
    private $_properties;
    
    /**
     * @var IValueEvaluator
     */
    private $_evaluator;
    
    /**
     * @var mixed
     */
    private $_default;
    
    public function __construct( $name, $properties, $evaluator, $default=null) {
        $this->_name        =   $name;
        $this->_properties  =   $properties;
        $this->_evaluator   =   $evaluator;
        $this->_default     =   $default;
    }
    
    public function getValue( $context=[]) 
    {
        if ( isset( $this->_properties[$this->_name])) {
            $value =  $this->_properties[$this->_name];
        } else {
            $value =  $this->_default;
        }
        
        return $this->_evaluator->evaluateString( $value, $context);
    }
    
    public function __toString() {
        return get_class( $this).'['.$this->_name.']'; 
    }
}
