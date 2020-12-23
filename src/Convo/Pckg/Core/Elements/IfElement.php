<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;

class IfElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    private $_test;

    /**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
    private $_then;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_elseIf;

    /**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
    private $_else;

    public function __construct($properties)
    {
        $this->_test = $properties['test'] ?? null;

        if (!$this->_test) {
            throw new \Exception('Missing required property [test] for simple if element');
        }

        $this->_then = $properties['then'] ?? [];
        if (empty($this->_then)) {
            throw new \Exception('Missing required property [then] for simple if element');
        }
        foreach ( $this->_then as $then) {
            $this->addChild( $then);
        }

        $this->_elseIf = $properties['else_if'] ?? [];
        foreach ($this->_elseIf as $elseIf) {
            $this->addChild($elseIf);
        }

        $this->_else = $properties['else'] ?? [];
        foreach ($this->_else as $else) {
            $this->addChild($else);
        }
    }

    public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
//         $then_result = StrUtil::parseBoolean($this->evaluateString($this->_test));
        $then_result = $this->evaluateString($this->_test);

        // $this->_logger->debug('Got then result ['.$then_result.']');

        if ($then_result)
        {
            $this->_logger->debug('Going to read then elements');

            foreach ($this->_then as $then) {
                $then->read($request, $response);
            }

            return;
        }

        if (!empty($this->_elseIf))
        {
            $this->_logger->debug('Else if elements present, going to iterate and check');

            /** @var \Convo\Pckg\Core\Elements\ElseIfElement $elseIf */
            foreach ($this->_elseIf as $elseIf) {
                $elif_result = $elseIf->evaluateTest();

                if ($elif_result) {
                    $this->_logger->debug('Found true result in else if element ['.$elseIf.']');
                    $elseIf->read($request, $response);
                    return;
                }
            }
        }

        $this->_logger->debug('Nothing matched, going to read else elements if any are present');
        
        foreach ($this->_else as $else) {
            $else->read($request, $response);
        }
    }

    public function __toString()
    {
        return get_class($this).'['.$this->_test.']';
    }
}
