<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

use Convo\Core\ConvoServiceInstance;
use Convo\Core\Factory\PackageProvider;
use Convo\Core\ComponentNotFoundException;

class DefaultIntentAndEntityLocator implements IIntentAndEntityLocator
{
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    
    /**
     * @var ConvoServiceInstance
     */
    private $_service;
    
    /**
     * @var PackageProvider
     */
    private $_packageProvider;

    /**
     * @var string
     */
    private $_platformId;
    
    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $platformId
     * @param ConvoServiceInstance $service
     * @param PackageProvider $pacakageProvider
     */
    public function __construct( $logger, $platformId, $service, $pacakageProvider)
    {
        $this->_logger = $logger;
        $this->_platformId = $platformId;
        $this->_service = $service;
        $this->_packageProvider = $pacakageProvider;
    }

    /**
     * @param string $intentName
     * @throws \Convo\Core\ComponentNotFoundException
     * @return \Convo\Core\Intent\IntentModel
     */
    public function getIntentModel( $intentName) 
    {
        $this->_logger->debug( 'Searching for intent ['.$intentName.']');
        try {
            $intent_model = $this->_service->getIntent( $intentName);
        } catch ( ComponentNotFoundException $e) {
            $sys_intent = $this->_packageProvider->getIntent( $intentName);
            $intent_model = $sys_intent->getPlatformModel( $this->_platformId);
        }

        return $intent_model;
    }
    
    /**
     * @param string $entityType
     * @throws \Convo\Core\ComponentNotFoundException
     * @return \Convo\Core\Intent\EntityModel
     */
    public function getEntityModel( $entityType)
    {
        try {
            $entity_model = $this->_service->getEntity( $entityType);
        } catch ( ComponentNotFoundException $e) {
            $system_entity = $this->_packageProvider->getEntity( $entityType);
            $entity_model = $system_entity->getPlatformModel( $this->_platformId);
        }
        
        return $entity_model;
    }
    
    
    
    // UTIL
    public function __toString()
    {
        return get_class($this) . '['.$this->_platformId.']';
    }
    
}