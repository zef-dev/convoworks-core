<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * Elements are used to be executed (read) in read phase.
 * 
 * @author Tole
 *
 */
interface IConversationElement extends \Convo\Core\Workflow\IServiceWorkflowComponent
{
	
	/**
	 * Executes internal logic (could be just delegating to child elements) in read phase.
	 * @param \Convo\Core\Workflow\IConvoRequest $request
	 * @param \Convo\Core\Workflow\IConvoResponse $response
	 * @throws \Convo\Core\StateChangedException
	 */
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response);
	
}