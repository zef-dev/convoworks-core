<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplSendEventCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{
	private $_commandEventArguments;
	private $_commandEventComponents;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_commandEventArguments = $properties['command_arguments'];
		$this->_commandEventComponents = $properties['command_components'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$commandEventArguments = $this->evaluateString($this->_commandEventArguments);
		$commandEventComponents = $this->evaluateString($this->_commandEventComponents);

		if (!is_array($commandEventArguments) || !is_array($commandEventComponents)) {
			throw new InvalidComponentDataException("APL Command Arguments or APL Command Components must be an array.");
		}

		$command = [
			'type' => 'SendEvent',
		];

		if (!empty($commandEventArguments)) {
			$command['arguments'] = $commandEventArguments;
		}

		if (!empty($commandEventComponents)) {
			$command['components'] = $commandEventComponents;
		}

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