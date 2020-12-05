<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

interface ISystemIntentRepository extends IPrefixed
{
    /**
     * @param string $name
     * @param string $platformId
     * @throws \Convo\Core\ComponentNotFoundException
     * @return \Convo\Core\Intent\SystemIntent
     */
    public function findPlatformIntent( $name, $platformId);
    
    
    /**
     * @param string $name
     * @throws \Convo\Core\ComponentNotFoundException
     * @return \Convo\Core\Intent\SystemIntent
     */
    public function getIntent( $name);
    
}