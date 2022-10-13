<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;


use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Workflow\IRunnableBlock;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;

class DefaultSpecialRoleBlock extends AbstractWorkflowContainerComponent implements IRunnableBlock
{

	private $_blockId;


	/**
	 * @var IConversationElement
	 */
	private $_elements = [];

	/**
	 * @var string
	 */
	private $_role;

	private $_blockName;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_blockId		=	$properties['block_id'];
		$this->_role		=	$properties['role'];
		$this->_blockName   =   $properties['name'] ?? 'Nameless block';

		if ( isset( $properties['elements'])) {
		    foreach ( $properties['elements'] as $element) {
		        $this->_elements[] = $element;
		        $this->addChild( $element);
			}
		}
	}

	public function getComponentId()
	{
		return $this->_blockId;
	}

	public function getRole()
	{
	    return $this->_role;
	}

	public function getName()
    {
        return $this->_blockName;
    }
    
    public function getElements()
    {
        return $this->_elements;
    }
    
    public function getProcessors()
    {
        return [];
    }

	public function read( IConvoRequest $request, IConvoResponse $response)
	{
	    foreach ( $this->_elements as $element)
	    {
	        /** @var IConversationElement $element */
	        $element->read( $request, $response);
	    }
	}

    /**
	 * {@inheritDoc}
	 * @see IRunnableBlock::run()
	 */
	public function run( IConvoRequest $request, IConvoResponse $response)
	{
	    $this->read( $request, $response);
	}

	public function getBlockParams( $scopeType)
	{
		// Is it top level block?
		if ( $this->getParent() === $this->getService()) {
			return $this->getService()->getComponentParams( $scopeType, $this);
		}

		return parent::getBlockParams( $scopeType);
	}


	// PREVIEW
    public function getPreview()
    {
        $pblock = new PreviewBlock($this->getName(), $this->getComponentId());
        $pblock->setLogger($this->_logger);

        // What the bot says first
        $section = new PreviewSection('Read', $this->_logger);
		$section->collect($this->getElements(), '\Convo\Core\Preview\IBotSpeechResource');
		$pblock->addSection($section);

        return $pblock;
    }

	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_blockId.']';
	}

}
