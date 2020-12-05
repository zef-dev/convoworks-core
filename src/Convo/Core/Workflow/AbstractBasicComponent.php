<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * This abstract class serves as base for other implementations. Besides implementing IBasicServiceComponent it setups the logger too.
 * @author Tole
 *
 */
abstract class AbstractBasicComponent implements \Convo\Core\Workflow\IBasicServiceComponent, \Psr\Log\LoggerAwareInterface
{

	/**
	 * @var \Convo\Core\ConvoServiceInstance
	 */
	private $_service;
	
	
	/**
	 * @var array
	 */
	protected $_properties;
	
	/**
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;
	
	/**
	 * Temporary as optional. Shouldd be obligate. 
	 * 
	 * @param array $properties
	 */
	public function __construct( $properties=null)
	{
		$this->_logger			=	new \Psr\Log\NullLogger();
		$this->_properties		=	$properties;
	}
	
	/**
	 * @ignore
	 * {@inheritDoc}
	 * @see \Psr\Log\LoggerAwareInterface::setLogger()
	 */
	public function setLogger( \Psr\Log\LoggerInterface $logger)
	{
		$this->_logger	=	$logger;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IBasicServiceComponent::getId()
	 */
	public function getId()
	{
	    if ( empty( $this->_properties)) {
	        throw new \Exception( 'Missing properties in ['.$this.']');
	    }
	    if ( !isset( $this->_properties['_component_id']) || empty( $this->_properties['_component_id'])) {
	        throw new \Exception( 'Missing _component_id in properties in ['.$this.']');
	    }
		return $this->_properties['_component_id'];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IBasicServiceComponent::getService()
	 */
	public function getService() {
		return $this->_service;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IBasicServiceComponent::setService()
	 */
	public function setService( \Convo\Core\ConvoServiceInstance $service) {
		$this->_service	=	$service;
	}
	
	// UTIL
	/**
	 * @ignore
	 * @return string
	 */
	public function __toString()
	{
		return get_class( $this);
	}
}