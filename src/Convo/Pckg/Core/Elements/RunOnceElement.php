<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\StateChangedException;
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
        $scope_type = $this->evaluateString($this->_scopeType);
        $params = $this->getService()->getComponentParams($scope_type, $this);

        $this->_logger->info('Got component params ['.$this->_scopeType.']['.print_r($params->getData(), true).']');

        $triggered = $params->getServiceParam('triggered');

        if (!$triggered) {
            $this->_logger->info('One-off element hasn\'t fired yet in scope ['.$this->_scopeType.']');

            if ($this->_children) {
                $this->_logger->debug('Reading children');
                
                foreach ($this->_children as $child) {
                    try {
                        $child->read($request, $response);
                    }
                    catch (StateChangedException $e) {
                        $this->_logger->info('State changed while reading children. Setting triggered to true before transition.');
                        $params->setServiceParam('triggered', true);
                        throw $e;
                    }
                }
            }

            $params->setServiceParam('triggered', true);
        }
        
        if ($triggered) {
            $this->_logger->info('One-off element already fired in ['.$this->_scopeType.'] scope. Checking for else elements');

            if ($this->_else) {
                $this->_logger->debug('Reading else flow');
                
                foreach ($this->_else as $else) {
                    $else->read($request, $response);
                }
            }
        }
    }
}