<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplSetFocusCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	private $_aplCommandComponentId;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandComponentId = $properties['command_component_id'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandComponentId = $this->evaluateString($this->_aplCommandComponentId);

		$command = [
			'type' => 'SetFocus',
		];

		if (!empty($aplCommandComponentId)) {
			$command['componentId'] = $aplCommandComponentId;
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