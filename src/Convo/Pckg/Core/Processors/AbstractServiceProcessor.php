<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Processors;


abstract class AbstractServiceProcessor extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationProcessor
{
	/**
	 * @var \Convo\Core\Workflow\IRequestFilter[]
	 */
	protected $_requestFilters	=	array();

	protected $_name;

	public function __construct( $properties)
	{
		parent::__construct( $properties);
		
		if ( isset( $properties['request_filters'])) {
		    foreach ( $properties['request_filters'] as $filter) {
		        $this->_requestFilters[]  =   $filter;
		        $this->addChild( $filter);
		    }
		}

		$this->_name = $properties['name'] ?? '';
	}
	
	protected function _getDefaultResultFilters( \Convo\Core\Workflow\IConvoRequest $request)
	{
		return $this->_requestFilters;
	}
	
	public function getName()
	{
		return $this->_name;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IConversationProcessor::filter()
	 */
	public function filter( \Convo\Core\Workflow\IConvoRequest $request)
	{
		$this->_logger->debug( 'Filtering request ['.$request.'] in ['.$this.']');

		foreach ( $this->_getDefaultResultFilters( $request) as $request_filter) {
			/* @var $request_filter \Convo\Core\Workflow\IRequestFilter */
			if ( $request_filter->accepts( $request)) {
				$this->_logger->debug( 'Applaying request filter ['.$request_filter.']');
				return $request_filter->filter( $request);
			}
		}
		
// 		throw new \Exception( 'Not supported request ['.$request.'] in ['.$this.']');
		$this->_logger->debug( 'Not accepted request ['.$request.'] in ['.$this.']');
		return new \Convo\Core\Workflow\DefaultFilterResult();
	}
	
	// UTIL
	public function __toString()
	{
	    return parent::__toString().'['.implode( ', ', array_map( function ( $item) { return strval( $item);}, $this->_requestFilters)).']';
	}
}