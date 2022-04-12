<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IOptionalElement;

class ElseIfElement extends AbstractWorkflowContainerComponent implements IConversationElement, IOptionalElement
{
    private $_test;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_then;

    public function __construct($config)
    {
        parent::__construct($config);
        
        $this->_test = $config['test'] ?? null;

        $this->_then = $config['then'] ?? [];
        
        foreach ($this->_then as $then) {
            $this->addChild($then);
        }
    }

    public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        foreach ($this->_then as $then) {
            $then->read($request, $response);
        }
    }

    /**
     * @deprecated
     * @return string
     */
    public function evaluateTest()
    {
        return $this->evaluateString($this->_test);
    }
    
    public function isEnabled()
    {
        return $this->evaluateString( $this->_test);
    }

    // UTIL

    public function __toString()
    {
        return get_class($this).'['.$this->_test.']';
    }
}