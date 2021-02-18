<?php declare(strict_types=1);

namespace Convo\Core\Preview;

class PreviewSection implements \Psr\Log\LoggerAwareInterface
{
    private $_name;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var PreviewUtterance[]
     */
    private $_utterances = [];

    public function __construct($name, \Psr\Log\LoggerInterface $logger = null)
    {
        $this->_name = $name;
        $this->_logger = $logger ?? new \Psr\Log\NullLogger();
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function isEmpty()
    {
        return count($this->_utterances) === 0;
    }

    public function collect($elements, $interface)
    {
        foreach ($elements as $element)
        {
            $speech = [];
            $this->_populateSpeech($speech, $element, $interface);

            foreach ($speech as $part)
            {
                if ($interface === '\Convo\Core\Preview\IBotSpeechResource') {
                    $this->_addUtterance(new PreviewUtterance($part->getSpeech()->getText()));
                }
                else if ($interface === '\Convo\Core\Preview\IUserSpeechResource') {
                    $this->_addUtterance(new PreviewUtterance($part->getText(), false, $part->getIntentSource()));
                }
                else {
                    throw new \Exception('Unknown speech resource interface ['.$interface.']');
                }
            }
        }
    }

    public function collectOne($element, $interface)
    {
        $speech = [];
        $this->_populateSpeech($speech, $element, $interface);

        foreach ($speech as $part)
        {
            if ($interface === '\Convo\Core\Preview\IBotSpeechResource') {
                $this->_addUtterance(new PreviewUtterance($part->getSpeech()->getText()));
            }
            else if ($interface === '\Convo\Core\Preview\IUserSpeechResource') {
                $this->_addUtterance(new PreviewUtterance($part->getSpeech()->getText(), false, $part->getSpeech()->getIntentSource()));
            }
            else {
                throw new \Exception('Unknown speech resource interface ['.$interface.']');
            }
        }
    }

    public function getData()
    {
        return [
            'name' => $this->_name,
            'utterances' => array_map(function ($utterance) { return $utterance->getData(); }, $this->_utterances)
        ];
    }

    private function _addUtterance(PreviewUtterance $utterance)
    {
        $this->_utterances[] = $utterance;
    }

    private function _populateSpeech(&$array, $element, $interface)
    {
        // being a speech resource takes precedence over being a container component.
        if (is_a($element, $interface))
        {
            $array[] = $element;
        }
        else if (is_a($element, '\Convo\Core\Workflow\IWorkflowContainerComponent'))
        {
            /** @var \Convo\Core\Workflow\IWorkflowContainerComponent $element */
            $this->_logger->debug('Element ['.$element.'] is a workflow container');
            $this->_flattenWorkflowContainers($array, $element, $interface);
        }
    }

    private function _flattenWorkflowContainers(&$array, $element, $interface)
    {
        $array = array_merge($array, $element->findChildren($interface));
        if (($index = array_search($element, $array)) !== false) {
            array_splice($array, $index, 1);
        }

        foreach ($array as $item) {
            if (is_a($item, '\Convo\Core\Workflow\IWorkflowContainerComponent')) {
                $this->_flattenWorkflowContainers($array, $item, $interface);
            }
        }
    }
}
