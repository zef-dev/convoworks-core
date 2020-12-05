<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Processors;


/**
 * @author tole
 *
 */
class ProcessProcessorFragment extends \Convo\Pckg\Core\Processors\AbstractServiceProcessor
{
	
	private $_fragmentId;

	
	public function __construct( $properties)
	{
		parent::__construct( $properties);
		
		$this->_fragmentId		=	$properties['fragment_id'];
	}
	
	public function process( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response, \Convo\Core\Workflow\IRequestFilterResult $result)
	{
		$processor	=	$this->_findProcessor();
		$processor->setParent( $this);
		$processor->process( $request, $response, $result);
	}
	
	public function filter( \Convo\Core\Workflow\IConvoRequest $request) 
	{
		$processor	=	$this->_findProcessor();
		$processor->setParent( $this);
		return $processor->filter( $request);
	}
	
	/**
	 * @return \Convo\Core\Workflow\IConversationProcessor
	 */
	private function _findProcessor()
	{
		$fragment	=	$this->getService()->findFragment( $this->evaluateString( $this->_fragmentId));
		
		if ( !is_a( $fragment, 'Convo\Core\Workflow\IConversationProcessor')) {
			throw new \Convo\Core\ComponentNotFoundException( 'Subroutne ['.$this->_fragmentId.'] is not processor');
		}
		
		/* @var $fragment  \Convo\Core\Workflow\IConversationProcessor */
		return $fragment;
	}

	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_fragmentId.']';
	}
}