<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

interface IServiceContext
{
	
	/**
	 * Reloads the data. Usually called after command process is done.
	 */
	public function init();

	/**
	 * @return string
	 */
	public function getId();
	
	/**
	 * Returns dessire object itself (e.g. connection)
	 * @return mixed 
	 */
	public function getComponent();
	
}