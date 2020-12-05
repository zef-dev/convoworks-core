<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

class IntentUtterance
{

    /**
     * @var string
     */
    private $_text;
    
    /**
     * @var array[]
     */
    private $_parts =   [];

    public function __construct()
    {
    }
    
    /**
     * @return string
     */
    public function getText()
    {
        return $this->_text;
    }
    
    /**
     * @return array[]
     */
    public function getParts()
    {
        return $this->_parts;
    }
    
    public function load( $data)
    {
        $this->_text    =   $data['raw'];
        $this->_parts   =   $data['model'];
        
//         {
//             "raw" : "guess the number",
//             "model" : [
//             { "text" : "guess", "type" : "@GameType", "slot_value" : "selectGameCommand"},
//             { "text" : "the number"}
//             ]
//         },
        
    }
    
    /**
     * @return string[]
     */
    public function getEntities()
    {
        $entities   =   [];
        
        foreach ( $this->_parts as $part) {
            $type   =   $part['type'] ?? null;
            if ( $type) {
                $entities[] =   $type;
            }
        }
        
        return $entities;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '['.$this->_text.']';
    }
}