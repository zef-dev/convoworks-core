<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class StandardCardElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
    private $_title;
    private $_text;
    private $_smallImageURL;
    private $_largeImageURL;

    public function __construct($properties)
    {
        parent::__construct($properties);
        $this->_title = $properties['title'] ;
        $this->_text = $properties['text'];
        $this->_smallImageURL = $properties['small_image_url'];
        $this->_largeImageURL = $properties['large_image_url'];
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $title = $this->evaluateString($this->_title);
        $text = $this->evaluateString($this->_text);
        $smallImageURL = $this->evaluateString($this->_smallImageURL);
        $largeImageURL = $this->evaluateString($this->_largeImageURL);

        if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest'))
        {
            $this->_logger->info('Going to send Standard Card...');
            /** @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
            $response->sendStandardCard($title, $text, $smallImageURL, $largeImageURL);
        }
    }
}
