<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplScrollCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	private $_aplCommandComponentId;
	private $_aplCommandDistance;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandComponentId 	= $properties['command_component_id'];
		$this->_aplCommandDistance 		= $properties['command_distance'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandComponentId = $this->evaluateString($this->_aplCommandComponentId);
		$aplCommandDistance = $this->evaluateString($this->_aplCommandDistance);

		$command = [
			'type' => 'Scroll'
		];

		if (!empty($aplCommandComponentId)) {
			$command['componentId'] = $aplCommandComponentId;
		}

		if (is_numeric($aplCommandDistance)) {
			$command['distance'] = intval($aplCommandDistance);
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