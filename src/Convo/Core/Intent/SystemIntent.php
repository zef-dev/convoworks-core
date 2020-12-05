<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

use Convo\Core\ComponentNotFoundException;

class SystemIntent
{

    /**
     * @var IntentModel[]
     */
    private $_platformModels    =   [];
    
    /**
     * @var string
     */
    private $_name;
    
    public function __construct( $name)
    {
        $this->_name    =   $name;
    }
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function getPlatforms()
    {
        return array_keys( $this->_platformModels);
    }
    
    /**
     * @param string $platformId
     * @param IntentModel $platformModel
     */
    public function setPlatformModel( $platformId, $platformModel)
    {
        $this->_platformModels[$platformId] =   $platformModel;
    }
    

    /**
     * @param string $platformId
     * @throws ComponentNotFoundException
     * @return \Convo\Core\Intent\IntentModel
     */
    public function getPlatformModel( $platformId)
    {
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
     * @return string
     */
    public function getPlatformName( $platformId)
    {
        return $this->getPlatformModel( $platformId)->getName();
    }
    

    // UTIL
    public function __toString()
    {
        return get_class($this) . '['.$this->_name.']['.implode( ', ', array_keys( $this->_platformModels)).']';
    }
}