<?php

namespace Convo\Core\Workflow;

use Convo\Core\Factory\ComponentDefinition;

/**
 * @author Tole
 * Enables property evaluation, with support for default value defined in component definition.
 */
class PropertyValue implements IPropertyValue
{
    /**
     * @var string
     */
    private $_name;
    
    /**
     * @var ComponentDefinition
     */
    private $_definition;
    
    /**
     * @var array
     */
    private $_properties;

    /**
     * @var IValueEvaluator
     */
    private $_evaluator;
    
    public function __construct( $name, $definition, $properties, $evaluator) {
        $this->_name        =   $name;
        $this->_definition  =   $definition;
        $this->_properties  =   $properties;
        $this->_evaluator   =   $evaluator;
    }
    
    public function getValue( $context=[]) {
        
        if ( isset( $this->_properties[$this->_name])) {
            $value =  $this->_properties[$this->_name];
        } else {
            $value =  $this->_definition->getDefaultValue( $this->_name);
        }
        
        return $this->_evaluator->evaluateString( $value, $context);
    }
    
    public function __toString() {
        return get_class( $this).'['.$this->_name.']['.$this->_definition.']'; 
    }
}
