<?php declare(strict_types=1);

namespace Convo\Core\Factory;

interface IComponentFactory
{
	
	/**
	 * @param array $properties
	 * @param \Convo\Core\ConvoServiceInstance $service
	 * @return \Convo\Core\Workflow\IBasicServiceComponent
	 */
	public function createComponent( $properties, $service);

}