<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;


class ReadBlockAgainElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
	    throw new \Convo\Core\StateChangedException( $this->getService()->getServiceState());
	}
}