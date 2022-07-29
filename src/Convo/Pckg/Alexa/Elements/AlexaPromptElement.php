<?php

namespace Convo\Pckg\Alexa\Elements;

class AlexaPromptElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements IAlexaDialogPrompt
{
    private $_prompt = '';

    public function __construct( $properties) {
        parent::__construct( $properties);

        $this->_prompt = $properties['alexa_prompt'];
    }

    public function getAlexaPrompt() {
        return $this->getService()->evaluateString($this->_prompt);
    }
}
