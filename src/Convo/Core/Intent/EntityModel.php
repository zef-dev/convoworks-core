<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

class EntityModel implements IEntityValueParser 
{
    /**
     * @var string
     */
    private $_name;
    
    /**
     * @var boolean
     */
    private $_isSystem;

    /**
     * @var EntityValue[]
     */
    private $_values    =   [];

    /**
     * @var IEntityValueParser[]
     */
    private $_parser;

    public function __construct( $name = null, $isSystem = false, $parser = null)
    {
        $this->_name        =   $name;
        $this->_isSystem    =   $isSystem;
        $this->_parser      =   $parser;
    }
    
    public function parseValue( $raw) {
        if ( $this->_parser) {
            return $this->_parser->parseValue( $raw);
        }
        // @TODO: remove this check - it should be specific to entity and done on creation
        if ( is_array( $raw) && isset( $raw['name'])) {
            return $raw['name'];
        }
        return $raw;
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * @return boolean
     */
    public function isSystem()
    {
        return $this->_isSystem;
    }
    
    /**
     * @return EntityValue[]
     */
    public function getValues()
    {
        return $this->_values;
    }
    
    /**
     * @param array $data
     */
    public function load( $data) {
        if ( isset( $data['name'])) {
            $this->_name    =   $data['name'];
        }
        
        foreach ( $data['values'] as $value_data) {
            $value  =   new EntityValue( $value_data['value']);
            if (isset($value_data['synonyms'])) {
				$value->addSynonyms( $value_data['synonyms']);
			}
            $this->_values[]    =   $value;
        }
//         {
//             "name" : "GameType",
//             "values" : [
//             {
//                 "value" : "pick",
//                 "synonyms" : [ "pick"]
//             },
//             {
//                 "value" : "guess",
//                 "synonyms" : [ "guess"]
//             }
//             ]
//         },
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '['.$this->_name.']['.$this->_isSystem.']';
    }
}