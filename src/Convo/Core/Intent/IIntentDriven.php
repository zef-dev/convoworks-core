<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

interface IIntentDriven
{

    
    /**
     * @param string $platformId
     * @return IntentModel
     */
    public function getPlatformIntentModel( $platformId);
    
}