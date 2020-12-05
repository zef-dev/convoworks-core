<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * Workflow processors are able to filter request (usually delegating to child request filters) and process it.
 * @author Tole
 *
 */
interface IConversationProcessor extends \Convo\Core\Workflow\IWorkflowContainerComponent
{
	/**
	 * Processes request into response using the passed result.    
	 * @param \Convo\Core\Workflow\IConvoRequest $request
	 * @param \Convo\Core\Workflow\IConvoResponse $response
	 * @param \Convo\Core\Workflow\IRequestFilterResult $result
	 * @throws \Convo\Core\StateChangedException
	 */
	public function process( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response, \Convo\Core\Workflow\IRequestFilterResult $result);
	
	/**
	 * Returns filter result applied against passed request.
	 * @param \Convo\Core\Workflow\IConvoRequest $request
	 * @return \Convo\Core\Workflow\IRequestFilterResult
	 */
	public function filter( \Convo\Core\Workflow\IConvoRequest $request);
	
}