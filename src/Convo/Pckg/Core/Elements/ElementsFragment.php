<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Preview\PreviewUtterance;

class ElementsFragment extends \Convo\Pckg\Core\Elements\ElementCollection implements \Convo\Core\Workflow\IIdentifiableComponent, \Convo\Core\Workflow\IFragmentComponent
{

	private $_fragmentId;

	private $_fragmentName;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_fragmentId	    =	$properties['fragment_id'];
		$this->_fragmentName    =   $properties['name'] ?? 'Nameless Elements Fragment';
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

	public function evaluateString( $string, $context=[])
	{
		$own_params		=	$this->getService()->getAllComponentParams( $this);
		return parent::evaluateString( $string, array_merge( $own_params, $context));
	}

	// PREVIEW
    public function getPreview()
    {
        $pblock = new PreviewBlock($this->getName(), $this->getComponentId());
        $pblock->setLogger($this->_logger);

        // What the bot says first
        $read = new PreviewSection('Read');
        foreach ($this->getElements() as $element)
        {
            /** @var \Convo\Core\Preview\IBotSpeechResource[] $speech */
            $speech = [];
            $this->_populateSpeech($speech, $element, '\Convo\Core\Preview\IBotSpeechResource');

            foreach ($speech as $part) {
                $read->addUtterance(new PreviewUtterance($part->getSpeech()->getText()));
            }
        }
        $pblock->addSection($read);

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

	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_fragmentId.']';
	}
}
