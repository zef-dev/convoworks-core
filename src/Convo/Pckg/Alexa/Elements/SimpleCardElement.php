<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class SimpleCardElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
    private $_title;
    private $_content;

    public function __construct($properties)
    {
        parent::__construct($properties);
        $this->_title = $properties['title'] ?? '';
        $this->_content = $properties['content'] ?? '';
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $title = $this->evaluateString($this->_title);
        $content = $this->evaluateString($this->_content);

        if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest'))
        {
            $this->_logger->info('Going to send Simple Card...');
            /** @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
            $response->sendSimpleCard($title, $content);
        }
    }
}
