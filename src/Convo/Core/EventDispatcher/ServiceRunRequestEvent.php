<?php

namespace Convo\Core\EventDispatcher;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Symfony\Contracts\EventDispatcher\Event;
use Convo\Core\ConvoServiceInstance;

class ServiceRunRequestEvent extends Event
{
    const NAME = 'service.run.request';

    
    /**
     * @var boolean
     */
    private $_testView;
    
    /**
     * @var IConvoRequest
     */
    private $_convoRequest;
    
    /**
     * @var IConvoResponse
     */
    private $_convoResponse;
    
    /**
     * @var ConvoServiceInstance
     */
    private $_service;
    
    /**
     * @var string
     */
    private $_variant;
    
    /**
     * @var \Throwable
     */
    private $_exception;

    public function __construct( $testView, $convoRequest, $convoResponse, $service, $variant, $exception=null)
    {
        $this->_testView        =   $testView;
        $this->_convoRequest    =   $convoRequest;
        $this->_convoResponse   =   $convoResponse;
        $this->_service         =   $service;
        $this->_variant         =   $variant;
        $this->_exception      =   $exception;
    }

    public function isTestView()
    {
        return $this->_testView;
    }

    public function getConvoRequest()
    {
        return $this->_convoRequest;
    }

    public function getConvoResponse()
    {
        return $this->_convoResponse;
    }

    public function getService()
    {
        return $this->_service;
    }

    public function getVariant()
    {
        return $this->_variant;
    }

    public function getException()
    {
        return $this->_exception;
    }

    public function __toString()
    {
        return get_class( $this).'['.$this->_testView.']['.$this->getService()->getId().']['.$this->_variant.']['.$this->getConvoRequest()->getPlatformId().']';
    }
}
