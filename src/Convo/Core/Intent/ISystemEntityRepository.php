<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

interface ISystemEntityRepository extends IPrefixed
{
    /**
     * @param string $name
     * @param string $platformId
     * @throws \Convo\Core\ComponentNotFoundException
     * @return \Convo\Core\Intent\EntityModel
     */
    public function findPlatformEntity( $name, $platformId);
    
    /**
     * @param string $name
     * @throws \Convo\Core\ComponentNotFoundException
     * @return \Convo\Core\Intent\SystemEntity
     */
    public function getEntity( $name);
}