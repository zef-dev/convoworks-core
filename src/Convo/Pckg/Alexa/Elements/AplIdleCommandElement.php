<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplIdleCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	private $_aplCommandDelay;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandDelay = $properties['command_delay'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandDelay = $this->evaluateString($this->_aplCommandDelay);

		if (!is_numeric($aplCommandDelay)) {
			throw new InvalidComponentDataException('Please make sure that the value of the command_delay property is numeric.');
		}

		$command = [
			'type' => 'Idle',
			'delay' => intval($aplCommandDelay),
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