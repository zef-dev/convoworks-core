<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;

class RunOnceElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
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
        $params = $this->getService()->getComponentParams($this->_scopeType, $this);

        $this->_logger->debug('Got component params ['.$this->_scopeType.']['.print_r($params->getData(), true).']');

        $triggered = $params->getServiceParam('triggered') ?? false;

        if (!$triggered) {
            $this->_logger->debug('One-off element hasn\'t fired yet in scope ['.$this->_scopeType.']');

            if ($this->_children) {
                $this->_logger->debug('Reading children');
                
                foreach ($this->_children as $child) {
                    $child->read($request, $response);
                }
            }

            $params->setServiceParam('triggered', true);
        }
        
        if ($triggered) {
            
            if ($this->_else) {
                $this->_logger->debug('Reading else flow');
                
                foreach ($this->_else as $else) {
                    $else->read($request, $response);
                }
            }
        }
    }
}