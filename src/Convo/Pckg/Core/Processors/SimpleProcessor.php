<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Processors;


class SimpleProcessor extends \Convo\Pckg\Core\Processors\AbstractServiceProcessor
{
	
	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	protected $_ok;
	
	protected $_specificOks		=	array();

	protected $_utterances		=	array();
	
	public function __construct( $properties)
	{
		parent::__construct( $properties);
		
		$this->_ok			=	$properties['ok'];
		if ( is_array( $this->_ok)) {
		    foreach ( $this->_ok as $ok) {
		        $this->addChild( $ok);
		    }
		}

		foreach ( $properties as $prop_name => $prop_val)
		{
			if ( stripos( $prop_name, 'nok_specific_') !== false) {
				/* @var $prop_val \Convo\Core\Workflow\IConversationElement */
				$this->_specificNoks[$prop_name]		=	$prop_val;
				$this->addChild( $prop_val);
			} else if ( stripos( $prop_name, 'ok_specific_') !== false) {
				/* @var $prop_val \Convo\Core\Workflow\IConversationElement */
				$this->_specificOks[$prop_name]		=	$prop_val;
				$this->addChild( $prop_val);
			}
		}
		
		if ( isset( $properties['utterances'])) {
			$this->_utterances		=	$properties['utterances'];
		}
	}
	
	public function process( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response, \Convo\Core\Workflow\IRequestFilterResult $result)
	{
		$this->_logger->debug( 'Processing OK');
		$this->_resolveResult( $request, $response);
	}
	
	/**
	 * @param \Convo\Core\Workflow\IConvoRequest $request
	 * @param \Convo\Core\Workflow\IConvoResponse $response
	 * @param string $type
	 */
	protected function _resolveResult( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response, $type=null)
	{
		$specific_ok_name	=	$type !== null ? "ok_specific_$type" : null;
		
		if ( $specific_ok_name && isset( $this->_specificOks[$specific_ok_name])) {
			$this->_logger->debug( 'Reading specific exit ['.$specific_ok_name.']');
			$this->_specificOks[$specific_ok_name]->read( $request, $response);
			return ;
		}
		
		$this->_logger->info( 'Reading default exit [ok]');
		foreach ( $this->_ok as $ok) {
		    $ok->read( $request, $response);
		}
	}
	
	// UTIL
	public function __toString()
	{
		return parent::__toString().'[]';
	}
}