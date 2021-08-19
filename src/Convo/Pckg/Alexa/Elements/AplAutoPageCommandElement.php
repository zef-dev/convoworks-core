<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplAutoPageCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	private $_aplCommandComponentId;
	private $_aplCommandCount;
	private $_aplCommandDelay;
	private $_aplCommandDuration;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandComponentId 	= $properties['command_component_id'];
		$this->_aplCommandCount 		= $properties['command_count'];
		$this->_aplCommandDuration 		= $properties['command_duration'];
		$this->_aplCommandDelay 		= $properties['command_delay'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandComponentId = $this->evaluateString($this->_aplCommandComponentId);
		$aplCommandCount = $this->evaluateString($this->_aplCommandCount);
		$aplCommandDuration = $this->evaluateString($this->_aplCommandDuration);
		$aplCommandDelay = $this->evaluateString($this->_aplCommandDelay);

		if (!is_numeric($aplCommandDelay)) {
			throw new InvalidComponentDataException('The provided duration is not valid');
		}

		$command = [
			'type' => 'AutoPage',
			'delay' => intval($aplCommandDelay)
		];

		if (!empty($aplCommandComponentId)) {
			$command['componentId'] = $aplCommandComponentId;
		}

		if (is_numeric($aplCommandCount)) {
			$command['count'] = intval($aplCommandCount);
		}

		if (is_numeric($aplCommandDuration)) {
			$command['duration'] = intval($aplCommandDuration);
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