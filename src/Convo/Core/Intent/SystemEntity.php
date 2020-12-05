<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

use Convo\Core\ComponentNotFoundException;

class SystemEntity
{
    /**
     * @var string
     */
    private $_name;
    
    /**
     * @var EntityModel[]
     */
    private $_platformModels =   [];
    
    public function __construct( $name)
    {
        $this->_name    =   $name;
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * @param string $platformId
     * @param EntityModel $entityModel
     */
    public function setPlatformModel( $platformId, $entityModel)
    {
        $this->_platformModels[$platformId] =   $entityModel;
    }
    
    /**
     * @param string $platformId
     * @throws \Exception
     * @throws ComponentNotFoundException
     * @return \Convo\Core\Intent\EntityModel
     */
    public function getPlatformModel( $platformId) {
        if ( empty( $platformId)) {
            throw new \Exception( 'Empty platform argument');
        }
        if ( !isset( $this->_platformModels[$platformId])) {
            throw new ComponentNotFoundException( 'No model for ['.$platformId.'] found in ['.$this.']');
        }
        
        return $this->_platformModels[$platformId];
    }
    
    /**
     * @param string $platformId
     * @throws \Exception
     * @throws ComponentNotFoundException
     * @return string
     */
    public function getPlatformName( $platformId) {
        return $this->getPlatformModel( $platformId)->getName();
    }
    
    /**
     * @return string[]
     */
    public function getPlatforms()
    {
        return array_keys( $this->_platformModels);
    }
    
    // UTIL
    public function __toString()
    {
        return get_class($this) . '['.$this->_name.']';
    }
}