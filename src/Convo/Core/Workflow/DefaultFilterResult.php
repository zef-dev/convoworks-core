<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

class DefaultFilterResult implements \Convo\Core\Workflow\IRequestFilterResult, \Psr\Log\LoggerAwareInterface
{
	private $_data		=	array();

	/**
	 * Logger
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	public function __construct()
	{
		$this->_logger	=	new \Psr\Log\NullLogger();
	}
	
	public function setLogger( \Psr\Log\LoggerInterface $logger)
	{
		$this->_logger	=	$logger;
	}

	public function isEmpty() {
		return empty( $this->_data);
	}
	
	public function isSlotEmpty( $name) {
	    return !isset( $this->_data[$name]) || trim( strval( $this->_data[$name])) === '';
	}
	
	public function getSlotValue( $name) {
		if ( isset( $this->_data[$name])) {
			return $this->_data[$name];
		}
		throw new \Exception( 'Slot ['.$name.'] not defined');
	}
	
	public function setSlotValue( $name, $value) {
		$this->_data[$name]	=	$value;
	}
	
	public function read( \Convo\Core\Workflow\IRequestFilterResult ...$results) 
	{
		foreach ($results as $result) 
		{
			foreach ( $result->getData() as $key=>$val) 
			{
				if ( isset( $this->_data[$key])) {
				    if ( is_array( $this->_data[$key]))
                        $this->_logger->warning( 'Overwriting existing key ['.$key.'] value ['.print_r( $this->_data[$key], true).'] with ['.print_r( $val, true).']');
                    else
					    $this->_logger->warning( 'Overwriting existing key ['.$key.'] value ['.$this->_data[$key].'] with ['.$val.']');
				}
				$this->_data[$key]	=	$val;
			}
		}
	}
	
	public function getData() {
		return $this->_data;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IRequestFilterResult::equals()
	 */
	public function equals( \Convo\Core\Workflow\IRequestFilterResult $result) {
		return $this->getData() == $result->getData();
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.json_encode( $this->_data).']';
	}
}