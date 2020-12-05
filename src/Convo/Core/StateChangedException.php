<?php declare(strict_types=1);

namespace Convo\Core;

class StateChangedException extends \Exception
{
    private $_state;
    
    public function __construct( $state, $previous=null)
    {
        parent::__construct( 'State changed to ['.$state.']', 0, $previous);
        $this->_state   =   $state;
    }
    
    public function getState() {
        return $this->_state;
    }
}