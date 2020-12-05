<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

class EntityValue
{
    /**
     * @var string
     */
    private $_value;
    
    /**
     * @var string[]
     */
    private $_synonyms  =   [];

    public function __construct( $value)
    {
        $this->_value   =   $value;
        $this->addSynonyms( [$value]);
    }
    
    /**
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }
    
    /**
     * @return string[]
     */
    public function getSynonims()
    {
        return array_values( $this->_synonyms);
    }
    
    /**
     * @param string[] $synonyms
     */
    public function addSynonyms( $synonyms) {
        foreach ( $synonyms as $synonym) {
            $this->_synonyms[$synonym]  =   $synonym;
        }
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '['.$this->_value.']';
    }
}