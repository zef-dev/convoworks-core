<?php

declare(strict_types=1);

namespace Convo\Core\Preview;


class ServicePreviewBuilder implements \Psr\Log\LoggerAwareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    private $_serviceId;

    /**
     * @var \Convo\Core\Preview\PreviewBlock[]
     */
    private $_blocks;

    public function __construct($serviceId)
    {
        $this->_serviceId = $serviceId;
        $this->_logger = new \Psr\Log\NullLogger();
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function addPreviewBlock(PreviewBlock $block)
    {
        $this->_blocks[] = $block;
    }

    public function read(\Convo\Core\ConvoServiceInstance $service)
    {
        /** @var \Convo\Core\Preview\ISpeechResource[] $bot_says */
        /** @var \Convo\Core\Preview\ISpeechResource[] $user_says */
        /** @var \Convo\Core\Preview\ISpeechResource[] $bot_responds */

        foreach ($service->getBlocks() as $block) {
            $bot_says     = [];
            $user_says    = [];
            $bot_responds = [];

            if (!$this->_isBlockApplicable($block)) {
                $this->_logger->warning('Skipping non applicable block [' . $block->getComponentId() . ']');
                continue;
            }

            // bot says
            foreach ($block->getElements() as $element)
            {
                $this->_populateSpeech($bot_says, $element, '\Convo\Core\Preview\IBotSpeechResource');
            }
            foreach ($block->getFallback() as $fallback)
            {
                $this->_populateSpeech($bot_says, $fallback, '\Convo\Core\Preview\IBotSpeechResource');
            }

            // user says + bot responds
            foreach ($block->getProcessors() as $processor)
            {
                // utterances in processors/intents are what the user says, rest is bot's response
                $this->_populateSpeech($user_says, $processor, '\Convo\Core\Preview\IUserSpeechResource');
                $this->_populateSpeech($bot_responds, $processor, '\Convo\Core\Preview\IBotSpeechResource');
            }

            $pblock = new PreviewBlock($block->getName(), $block->getComponentId());
            $pblock->setLogger($this->_logger);

            $pblock->collectKind($bot_says,     'bot_says');
            $pblock->collectKind($user_says,    'user_says');
            $pblock->collectKind($bot_responds, 'bot_responds');

            $this->_blocks[] = $pblock;
        }

        foreach ($service->getFragments() as $fragment) {
			$bot_says     = [];
			$user_says    = [];
			$bot_responds = [];

			$fragment_type = '';

			if (is_a($fragment, '\Convo\Pckg\Core\Elements\ElementsFragment'))
			{
				$this->_populateSpeech($bot_says, $fragment, '\Convo\Core\Preview\IBotSpeechResource');

				$fragment_type = 'ReadFragment';
			}
			else if (is_a($fragment, '\Convo\Pckg\Core\Processors\ProcessorFragment'))
			{
				$this->_populateSpeech($user_says, $fragment, '\Convo\Core\Preview\IUserSpeechResource');
				$this->_populateSpeech($bot_responds, $fragment, '\Convo\Core\Preview\IBotSpeechResource');

				$fragment_type = 'ProcessFragment';
			}
			else
			{
				throw new \Exception('Unexpected fragment class ['.get_class($fragment).']');
			}

			$pblock = new PreviewBlock($fragment->getWorkflowName(), $fragment_type.' ['.$fragment->getId().']');
			$pblock->setLogger($this->_logger);

			$pblock->collectKind($bot_says,     'bot_says');
			$pblock->collectKind($user_says,    'user_says');
			$pblock->collectKind($bot_responds, 'bot_responds');

			$this->_blocks[] = $pblock;
		}
    }

    public function readBlock($block)
    {
        $bot_says     = [];
        $user_says    = [];
        $bot_responds = [];

        if (!$this->_isBlockApplicable($block)) {
            $this->_logger->warning('Skipping non applicable block [' . $block->getComponentId() . ']');
            return null;
        }

        // bot says
        foreach ($block->getElements() as $element)
        {
            $this->_populateSpeech($bot_says, $element, '\Convo\Core\Preview\IBotSpeechResource');
        }
        foreach ($block->getFallback() as $fallback)
        {
            $this->_populateSpeech($bot_says, $fallback, '\Convo\Core\Preview\IBotSpeechResource');
        }

        // user says + bot responds
        foreach ($block->getProcessors() as $processor)
        {
            // utterances in processors/intents are what the user says, rest is bot's response
            $this->_populateSpeech($user_says, $processor, '\Convo\Core\Preview\IUserSpeechResource');
            $this->_populateSpeech($bot_responds, $processor, '\Convo\Core\Preview\IBotSpeechResource');
        }

        $pblock = new PreviewBlock($block->getName(), $block->getComponentId());
        $pblock->setLogger($this->_logger);

        $pblock->collectKind($bot_says,     'bot_says');
        $pblock->collectKind($user_says,    'user_says');
        $pblock->collectKind($bot_responds, 'bot_responds');

        $this->_blocks[] = $pblock;
    }

    public function readFragment($fragment)
    {
        $bot_says     = [];
        $user_says    = [];
        $bot_responds = [];

        $fragment_type = '';

        if (is_a($fragment, '\Convo\Pckg\Core\Elements\ElementsFragment'))
        {
            $this->_populateSpeech($bot_says, $fragment, '\Convo\Core\Preview\IBotSpeechResource');

            $fragment_type = 'ReadFragment';
        }
        else if (is_a($fragment, '\Convo\Pckg\Core\Processors\ProcessorFragment'))
        {
            $this->_populateSpeech($user_says, $fragment, '\Convo\Core\Preview\IUserSpeechResource');
            $this->_populateSpeech($bot_responds, $fragment, '\Convo\Core\Preview\IBotSpeechResource');

            $fragment_type = 'ProcessFragment';
        }
        else
        {
            throw new \Exception('Unexpected fragment class ['.get_class($fragment).']');
        }

        $pblock = new PreviewBlock($fragment->getWorkflowName(), $fragment_type.' ['.$fragment->getId().']');
        $pblock->setLogger($this->_logger);

        $pblock->collectKind($bot_says,     'bot_says');
        $pblock->collectKind($user_says,    'user_says');
        $pblock->collectKind($bot_responds, 'bot_responds');

        $this->_blocks[] = $pblock;
    }

    public function getPreview()
    {
        $preview = [
            'service_id' => $this->_serviceId,
            'blocks' => []
        ];

        foreach ($this->_blocks as $block)
        {
            $preview['blocks'][] = $block->getData();
        }

        return $preview;
    }

    private function _isBlockApplicable( \Convo\Core\Workflow\IRunnableBlock $block)
    {
        // session start is fine
        if ($block->getComponentId() === '__sessionStart') {
            return true;
        }

        // otherwise only non system blocks
        return strpos($block->getComponentId(), '__') !== 0;
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

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
