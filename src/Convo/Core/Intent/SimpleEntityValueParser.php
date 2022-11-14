<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

class SimpleEntityValueParser implements IEntityValueParser
{

    private $_key;
    
    public function __construct( $key) {
        $this->_key = $key;
    }
    /**
     * @param mixed $raw
     * @return string
     */
    public function parseValue( $raw) {
        if ( isset( $raw[$this->_key])) {
            return $raw[$this->_key];
        }
        throw new \Exception( 'No key ['.$this->_key.'] found in ['.print_r( $raw, true).']');
    }
    
    public function __toString() {
        return get_class( $this).'['.$this->_key.']';
    }
}