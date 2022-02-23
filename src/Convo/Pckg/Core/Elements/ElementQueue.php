<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class ElementQueue extends AbstractWorkflowContainerComponent implements IConversationElement
{
    private $_scopeType;

    /**
     * @var IConversationElement[]
     */
    private $_elements;

    /**
     * @var IConversationElement[]
     */
    private $_done;

    private $_shouldReset;

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_scopeType = $properties['scope_type'];

        $this->_shouldReset = $properties['should_reset'];

        $this->_elements = $properties['elements'] ?: [];

        foreach ($this->_elements as $element) {
            $this->addChild($element);
            $element->setParent($this);
        }

        $this->_done = $properties['done'] ?: [];

        foreach ($this->_done as $done) {
            $this->addChild($done);
            $done->setParent($this);
        }
    }
    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $params = $this->getService()->getComponentParams($this->evaluateString($this->_scopeType), $this);
        $current_index = $params->getServiceParam('index') ?: 0;

        if ($current_index === count($this->_elements))
        {
            $this->_logger->info('Current index ['.$current_index.'] falls outside of elements count.');

            $should_reset = $this->evaluateString($this->_shouldReset);

            if ($should_reset) {
                $this->_logger->info('Resetting index to 0');
                $current_index = 0;
            } else {
                $this->_logger->debug('Going to read Done flow');

                foreach ($this->_done as $done) {
                    $done->read($request, $response);
                }

                return;
            }
        }

        if (isset($this->_elements[$current_index])) {
            $this->_elements[$current_index]->read($request, $response);
            $params->setServiceParam('index', ($current_index + 1));
        }
    }
}
