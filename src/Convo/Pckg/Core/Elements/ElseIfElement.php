<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;

class ElseIfElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    private $_test;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_then;

    public function __construct($config)
    {
        parent::__construct($config);
        
        if (!isset($config['test'])) {
            throw new \Exception('Missing property [then] in ['.$this.']');
        }
        $this->_test = $config['test'];

        if (!isset($config['then'])) {
            throw new \Exception('Missing property [then] in ['.$this.']');
        }

        $this->_then = $config['then'];
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

    public function evaluateTest()
    {
//         return StrUtil::parseBoolean($this->evaluateString($this->_test));
        return $this->evaluateString($this->_test);
    }

    // UTIL

    public function __toString()
    {
        return get_class($this).'['.$this->_test.']';
    }
}