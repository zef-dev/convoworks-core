<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplSpeakListCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	private $_aplCommandComponentId;
	private $_aplCommandAlign;
	private $_aplCommandCount;
	private $_aplCommandMinimumDwellTime;
	private $_aplCommandStart;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandComponentId 		= $properties['command_component_id'];
		$this->_aplCommandAlign 			= $properties['command_align'];
		$this->_aplCommandCount 			= $properties['command_count'];
		$this->_aplCommandMinimumDwellTime 	= $properties['command_minimum_dwell_time'];
		$this->_aplCommandStart				= $properties['command_start'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandComponentId = $this->evaluateString($this->_aplCommandComponentId);
		$aplCommandAlign = $this->evaluateString($this->_aplCommandAlign);
		$aplCommandCount = $this->evaluateString($this->_aplCommandCount);
		$aplCommandMinimumDwellTime = $this->evaluateString($this->_aplCommandMinimumDwellTime);
		$aplCommandStart = $this->evaluateString($this->_aplCommandStart);

		if (!is_numeric($aplCommandCount) || !is_numeric($aplCommandStart)) {
			throw new InvalidComponentDataException('The provided count or start is not valid.');
		}

		$command = [
			'type' => 'SpeakList',
			'start' => intval($aplCommandStart),
			'count' => intval($aplCommandCount)
		];

		if (!empty($aplCommandComponentId)) {
			$command['componentId'] = $aplCommandComponentId;
		}

		if (!empty($aplCommandAlign)) {
			$command['align'] = $aplCommandAlign;
		}

		if (is_numeric($aplCommandMinimumDwellTime)) {
			$command['minimumDwellTime'] = intval($aplCommandMinimumDwellTime);
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