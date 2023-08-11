<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

/**
 * This elements stops execution of the current request.
 * 
 * @author tole
 */
class EndRequestElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
	    $this->_logger->info('Sending end request signal ...');
		throw new \Convo\Core\EndRequestException();
	}
	
	// UTIL
	public function __toString()
	{
		return get_class( $this);
	}
}