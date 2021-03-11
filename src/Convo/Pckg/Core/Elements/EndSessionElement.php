<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;



/**
 * This elements just sets the session end flag in response. This intent should be used when matched Amazon.StopIntent.
 * 
 * @author tole
 */
class EndSessionElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
	    $this->_logger->info('Sending end session signal ...');
		
		$response->setShouldEndSession(true);
		
		throw new \Convo\Core\StateChangedException('');
	}
	
	// UTIL
	public function __toString()
	{
		return get_class( $this);
	}
}