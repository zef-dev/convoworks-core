<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Processors;

use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Preview\PreviewUtterance;

class ProcessorFragment extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationProcessor, \Convo\Core\Workflow\IFragmentComponent, \Convo\Core\Workflow\IIdentifiableComponent
{

	/**
	 * @var \Convo\Core\Workflow\IConversationProcessor[]
	 */
	private $_processors   =   [];

	/**
	 * @var \Convo\Core\Workflow\IConversationProcessor
	 */
	private $_matched;

	private $_fragmentId;

	private $_fragmentName;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_fragmentId	=	$properties['fragment_id'];

		if ( isset( $properties['processors']) && is_array( $properties['processors'])) {
		    foreach ( $properties['processors'] as $processor) {
		        $this->_processors[]	=	$processor;
		        $this->addChild( $processor);
		    }
		}

		$this->_fragmentName = $properties['name'] ?? 'Nameless Process Fragment';
	}

	public function getComponentId()
	{
		return $this->_fragmentId;
	}

	public function getName()
	{
		return $this->_fragmentId;
	}

	public function getWorkflowName()
    {
        return $this->_fragmentName;
	}
	
	// PREVIEW
    public function getPreview()
    {
        $pblock = new PreviewBlock($this->getName(), $this->getComponentId());
        $pblock->setLogger($this->_logger);

        // User <-> Bot back and forth
        foreach ($this->_processors as $processor)
        {
            $processor_section = new PreviewSection((new \ReflectionClass($processor))->getShortName().' ['.$processor->getId().']');

            /** @var \Convo\Core\Preview\IBotSpeechResource[] $user */
            $user = [];
            /** @var \Convo\Core\Preview\IBotSpeechResource[] $bot */
            $bot = [];
            $this->_populateSpeech($user, $processor, '\Convo\Core\Preview\IUserSpeechResource');
			$this->_populateSpeech($bot, $processor, '\Convo\Core\Preview\IBotSpeechResource');
			
			if (empty($user) && empty($bot)) {
				$this->_logger->debug('No user utterances or bot responses, skipping.');
				continue;
			}

            foreach ($user as $user_part)
            {
				$speech = $user_part->getSpeech();
                $utterance = new PreviewUtterance($speech->getText(), false, $speech->getIntentSource());
                $processor_section->addUtterance($utterance);
            }

            foreach ($bot as $bot_part)
            {
                $utterance = new PreviewUtterance($bot_part->getSpeech()->getText());
                $processor_section->addUtterance($utterance);
            }

            $pblock->addSection($processor_section);
        }

        return $pblock;
    }

    protected function _populateSpeech(&$array, $element, $interface)
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

    protected function _flattenWorkflowContainers(&$array, $element, $interface)
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

    /**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IConversationProcessor::process()
	 */
	public function process( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response, \Convo\Core\Workflow\IRequestFilterResult $result)
	{
	    if ( !is_a( $this->_matched, '\Convo\Core\Workflow\IConversationProcessor')) {
	        throw new \Exception( 'Expected to find [\Convo\Core\Workflow\IConversationProcessor] object here');
	    }
		$this->_matched->process( $request, $response, $result);
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IConversationProcessor::filter()
	 */
	public function filter( \Convo\Core\Workflow\IConvoRequest $request)
	{
	    foreach ( $this->_processors as $processor) {
	        $result    =   $processor->filter( $request);
	        if ( $result->isEmpty()) {
	            continue;
	        }
	        $this->_matched    =   $processor;
	        return $result;
	    }

		return new \Convo\Core\Workflow\DefaultFilterResult();
	}


	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_fragmentId.']['.$this->_matched.']';
	}
}
