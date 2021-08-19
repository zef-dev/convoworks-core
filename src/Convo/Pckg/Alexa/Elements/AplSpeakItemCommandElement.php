<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplSpeakItemCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	private $_aplCommandComponentId;
	private $_aplCommandAlign;
	private $_aplCommandHighlightMode;
	private $_aplCommandMinimumDwellTime;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandComponentId 		= $properties['command_component_id'];
		$this->_aplCommandAlign 			= $properties['command_align'];
		$this->_aplCommandHighlightMode 	= $properties['command_highlight_mode'];
		$this->_aplCommandMinimumDwellTime 	= $properties['command_minimum_dwell_time'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandComponentId = $this->evaluateString($this->_aplCommandComponentId);
		$aplCommandAlign = $this->evaluateString($this->_aplCommandAlign);
		$aplCommandHighlightMode = $this->evaluateString($this->_aplCommandHighlightMode);
		$aplCommandMinimumDwellTime = $this->evaluateString($this->_aplCommandMinimumDwellTime);

		$command = [
			'type' => 'SpeakItem'
		];

		if (!empty($aplCommandComponentId)) {
			$command['componentId'] = $aplCommandComponentId;
		}

		if (!empty($aplCommandHighlightMode)) {
			$command['highlightMode'] = $aplCommandHighlightMode;
		}

		if (!empty($aplCommandAlign)) {
			$command['align'] = $aplCommandAlign;
		}

		if (!empty($aplCommandMinimumDwellTime) && is_numeric($aplCommandMinimumDwellTime)) {
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