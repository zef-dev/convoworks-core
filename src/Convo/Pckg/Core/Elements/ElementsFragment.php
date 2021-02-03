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
        $read->setLogger($this->_logger);

        try {
            $read->collect($this->getElements(), '\Convo\Core\Preview\IBotSpeechResource');

            if (!$read->isEmpty()) {
                $pblock->addSection($read);
            }
        } catch (\Exception $e) {
            $this->_logger->error($e);
        }

        return $pblock;
    }

	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_fragmentId.']';
	}
}
