<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;



/**
 * This elements runs the read() method of referenced ReadFragment.
 * 
 * @author tole
 */
class ReadElementsFragment extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	
	private $_fragmentId;
	
	public function __construct( $properties)
	{
		parent::__construct( $properties);
		
		$this->_fragmentId		=	$properties['fragment_id'];
	}
	
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$block	=	$this->_findFragment();
		$block->setParent( $this);
		$block->read( $request, $response);
	}
	
	/**
	 * @return \Convo\Core\Workflow\IConversationElement
	 */
	private function _findFragment()
	{
		$fragment	=	$this->getService()->findFragment( $this->evaluateString( $this->_fragmentId));
		
		if ( !is_a( $fragment, 'Convo\Core\Workflow\IConversationElement')) {
			throw new \Convo\Core\ComponentNotFoundException( 'Fragment ['.$this->_fragmentId.'] is not an element');
		}
		
		/* @var $fragment  \Convo\Core\Workflow\IConversationElement */
		return $fragment;
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->_fragmentId.']';
	}
}