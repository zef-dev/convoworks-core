<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * This imterface sets the minimal requrement for components - to be aware of service and to have own unique id.
 * @author Tole
 *
 */
interface IBasicServiceComponent
{
	
	/**
	 * Uniquie component identifier
	 * @return string
	 */
	public function getId();
	
	/**
	 * Returns previously set service instance.
	 * @return \Convo\Core\ConvoServiceInstance
	 */
	public function getService();
	
	/**
	 * Sets the service for the component
	 * @param \Convo\Core\ConvoServiceInstance $service
	 */
	public function setService( \Convo\Core\ConvoServiceInstance $service);
	
	
	
}