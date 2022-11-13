<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

interface IIntentAndEntityLocator
{

    /**
     * @param string $intentName
     * @throws \Convo\Core\ComponentNotFoundException
     * @return \Convo\Core\Intent\IntentModel
     */
    public function getIntentModel( $intentName);
    
    /**
     * @param string $entityType
     * @throws \Convo\Core\ComponentNotFoundException
     * @return \Convo\Core\Intent\EntityModel
     */
    public function getEntityModel( $entityType);
    
}