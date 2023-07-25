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
		$block    =   $this->_findFragment();
		$found    =   false;
		$parent   =   $this->getParent();
		while ( !$parent->isRoot()) {
		    if ( $parent === $block) {
		        $this->_logger->warning( 'Self include in ['.$this.'] while including fragment ['.$block.']');
		        $found = true;
		        break;
		    }
		    $parent =   $parent->getParent();
		}
		
		if ( !$found) {
		    $block->setParent( $this);
		}
		
		$block->read( $request, $response);
	}
	
	/**
	 * @return \Convo\Core\Workflow\IConversationElement
	 */
	private function _findFragment()
	{
	    $fragment_id   =   $this->evaluateString( $this->_fragmentId);
	    $this->_logger->info( 'Searching for fragment ['.$fragment_id.']');
	    $fragment	=	$this->getService()->findFragment( $fragment_id);
		
		if ( !is_a( $fragment, 'Convo\Core\Workflow\IConversationElement')) {
		    throw new \Convo\Core\ComponentNotFoundException( 'Fragment ['.$this->_fragmentId.']['.$fragment_id.'] is not an element');
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