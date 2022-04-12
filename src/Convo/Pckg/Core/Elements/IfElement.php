<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IOptionalElement;

class IfElement extends AbstractWorkflowContainerComponent implements IConversationElement, IOptionalElement
{
    private $_test;

    /**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
    private $_then;

    /**
     * @var \Convo\Pckg\Core\Elements\ElseIfElement[]
     */
    private $_elseIf;

    /**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
    private $_else;

    public function __construct($properties)
    {
        parent::__construct($properties);
        
        $this->_test = $properties['test'] ?? null;

        $this->_then = $properties['then'] ?? [];
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
        $then_result = $this->evaluateString($this->_test);

        if ($then_result)
        {
            $this->_logger->info('Going to read then elements');

            foreach ($this->_then as $then) {
                $then->read($request, $response);
            }

            return;
        }

        if (!empty($this->_elseIf))
        {
            $this->_logger->info('Else if elements present, going to iterate and check');

            /** @var \Convo\Pckg\Core\Elements\ElseIfElement $elseIf */
            foreach ($this->_elseIf as $elseIf) {
                if ($elseIf->isEnabled()) {
                    $this->_logger->info('Found true result in else if element ['.$elseIf.']');
                    $elseIf->read($request, $response);
                    return;
                }
            }
        }

        $this->_logger->info('Nothing matched, going to read else elements if any are present');

        foreach ($this->_else as $else) {
            $else->read($request, $response);
        }
    }
    
    public function isEnabled()
    {
        if ( !empty( $this->_else) || !empty( $this->_elseIf)) {
            return true;
        }
        
        return $this->evaluateString( $this->_test);
    }
    
    public function __toString()
    {
        return get_class($this).'['.$this->_test.']';
    }
}
