<?php


namespace Convo\Pckg\Alexa\Elements;


class AplFinishCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	public function __construct( $properties)
	{
		parent::__construct( $properties);
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$command = [
			'type' => 'Finish',
		];

		if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
		{
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
			if ($request->getIsAplSupported()) {
				$response->addAplCommand($command);
			}
		}
	}
}