<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

use Convo\Core\ComponentNotFoundException;

class IntentModel
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
     * @var IntentUtterance[]
     */
    private $_utterances    =   [];

    private $_events = [];

    private $_isFallback = false;

    public function __construct( $name=null, $isSystem=false)
    {
        $this->_name        =   $name;
        $this->_isSystem    =   $isSystem;
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
     * @return IntentUtterance[]
     */
    public function getUtterances()
    {
        return $this->_utterances;
    }

    /**
     * @param IntentUtterance $utterance
     */
    public function addUtterance( $utterance) {
        $this->_utterances[]    =   $utterance;
    }

    public function addEvent( $event) {
        $this->_events[]    =   $event;
    }

    public function getEvents() {
        return $this->_events;
    }

    public function setIsFallback($isFallback) {
        $this->_isFallback = $isFallback;
    }

    public function isFallback() {
        return $this->_isFallback;
    }

    /**
     * @return string[]
     */
    public function getEntities()
    {
        $entities   =   [];

        foreach ( $this->_utterances as $utterance) {
            $entities   =   array_merge( $entities, $utterance->getEntities());
        }

        return $entities;
    }
    
    /**
     * @param string $slot
     * @throws ComponentNotFoundException
     * @return string
     */
    public function getEntityTypeBySlot( $slot)
    {
        foreach ( $this->_utterances as $utterance) {
            foreach ( $utterance->getParts() as $part) {
                if ( $part['slot_value'] === $slot) {
                    return $part['type'];
                }
            }
        }
        
        throw new ComponentNotFoundException( 'Entity for slot ['.$slot.'] not found in intent ['.$this->getName().']');
    }

    /**
     * @param array $data
     */
    public function load( $data)
    {
        $this->_name    =   $data['name'];
        if ( !( isset( $data['type']) && $data['type'] === 'custom')) {
            $this->_isSystem    =   true;
        }
        if ( isset( $data['utterances']) && is_array( $data['utterances'])) {
            foreach ( $data['utterances'] as $utterance_data) {
                $utterance  =   new IntentUtterance();
                $utterance->load( $utterance_data);
                $this->addUtterance( $utterance);
            }
        }

        if ( isset( $data['events']) && is_array( $data['events'])) {
            foreach ( $data['events'] as $event_data) {
                $this->addEvent( $event_data);
            }
        }

        if ( isset( $data['fallback']) && is_bool($data['fallback'])) {
            if ($data['fallback']) {
                $this->setIsFallback(true);
            }
        }

//         {
//             "name" : "NoIntent",
//             "type" : "custom",
//             "utterances" : [
//             {
//                 "raw" : "no",
//                 "model" : [
//                 { "text" : "no"}
//                 ]
//             }, {
//                 "raw" : "mope",
//                 "model" : [
//                 { "text" : "nope"}
//                 ]
//                 }, {
//                     "raw" : "negative",
//                     "model" : [
//                     { "text" : "negative"}
//                     ]
//                 }
//                 ]
//         }
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '['.$this->_name.']['.$this->_isSystem.']';
    }
}
