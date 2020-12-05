<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

class ServiceIntentModel
{

    /**
     * @var IntentModel[]
     */
    private $_intents    =   [];
    
    /**
     * @var EntityModel[]
     */
    private $_entities   =   [];
    
    public function __construct()
    {
    }
    
    /**
     * @return IntentModel[]
     */
    public function getIntents()
    {
        return array_values( $this->_intents);
    }
    
    /**
     * @param IntentModel $intent
     */
    public function addIntent( $intent) {
        $this->_intents[$intent->getName()]   =   $intent;
    }
    
    /**
     * @return EntityModel[]
     */
    public function getEntities()
    {
        return array_values( $this->_entities);
    }
    
    /**
     * @param EntityModel $entity
     */
    public function addEntity( $entity) {
        $this->_entities[$entity->getName()]  =   $entity;
    }
    
    public function getIntentEntities()
    {
        $entities   =   [];
        
        foreach ( $this->_intents as $intent) {
            $entities   =   array_merge( $entities, $intent->getEntities());
        }
        
        return array_unique( $entities);
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}