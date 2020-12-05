<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

interface IIdentifiableComponent
{
	
	/**
	 * Returns component id
	 * @return string
	 */
	public function getComponentId();
	
	
}