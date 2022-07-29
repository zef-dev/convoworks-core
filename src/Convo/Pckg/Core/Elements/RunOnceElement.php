<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\StateChangedException;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IOptionalElement;

class RunOnceElement extends AbstractWorkflowContainerComponent implements IConversationElement, IOptionalElement
{
    const PARAM_NAME_TRIGGERED  =   'triggered';
    
    private $_scopeType;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_children;
    
    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_else;

    public function __construct($properties)
    {
        parent::__construct( $properties);
        
        $this->_scopeType = $properties['scope_type'];

        $this->_children = $properties['child'] ?? [];
        
        foreach ($this->_children as $child) {
            parent::addChild($child);
        }
        
        $this->_else = $properties['else'] ?? [];
        
        foreach ($this->_else as $else) {
            parent::addChild($else);
        }
    }

    public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        if ( !$this->_isTriggered()) 
        {
            $this->_logger->info('One-off element hasn\'t fired yet in scope ['.$this->_scopeType.']');
            
            foreach ( $this->_children as $child)
            {
                try {
                    $child->read($request, $response);
                }
                catch (StateChangedException $e) {
                    $this->_logger->info( 'State changed while reading children. Setting triggered to true before transition.');
                    $this->_markAsTriggered();
                    throw $e;
                }
            }

            $this->_markAsTriggered();
        }
        else 
        {
            $this->_logger->info('One-off element already fired in ['.$this->_scopeType.'] scope. Checking for else elements');

            foreach ($this->_else as $else) {
                $else->read($request, $response);
            }
        }
    }
    
    private function _getParams()
    {
        $scope_type =   $this->evaluateString( $this->_scopeType);
        return $this->getService()->getComponentParams( $scope_type, $this);
    }
    
    private function _isTriggered()
    {
        return $this->_getParams()->getServiceParam( self::PARAM_NAME_TRIGGERED);
    }
    
    private function _markAsTriggered()
    {
        $this->_getParams()->setServiceParam( self::PARAM_NAME_TRIGGERED, true);
    }
    
    public function isEnabled()
    {
        if ( !empty( $this->_else)) {
            return true;
        }
        
        return $this->evaluateString( $this->_test);
    }
}