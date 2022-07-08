<?php


namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\IAlexaResponseType;

class DialogDelegateElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{

	public function __construct( $properties)
	{
		parent::__construct( $properties);
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
        /* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
        /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
        if ( is_a( $request, 'Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
            $response->prepareResponse(IAlexaResponseType::DIALOG_DELEGATE_DIRECTIVE);
            $response->delegate();
        }
	}
}
