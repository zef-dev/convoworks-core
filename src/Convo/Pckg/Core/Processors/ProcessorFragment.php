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
            $processor_section = new PreviewSection('Process Fragment '.(new \ReflectionClass($processor))->getShortName().' ['.$processor->getId().']');
            $processor_section->setLogger($this->_logger);

            try {
                $processor_section->collectOne($processor, '\Convo\Core\Preview\IUserSpeechResource');
                $processor_section->collectOne($processor, '\Convo\Core\Preview\IBotSpeechResource');

                if (!$processor_section->isEmpty()) {
                    $pblock->addSection($processor_section);
                }
            } catch (\Exception $e) {
                $this->_logger->error($e);
                continue;
            }
        }

        return $pblock;
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
