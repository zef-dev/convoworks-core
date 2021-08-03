<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplScrollToComponentCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	private $_aplCommandComponentId;
	private $_aplCommandAlign;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandComponentId 	= $properties['command_component_id'];
		$this->_aplCommandAlign 		= $properties['command_align'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandComponentId = $this->evaluateString($this->_aplCommandComponentId);
		$aplCommandAlign = $this->evaluateString($this->_aplCommandAlign);

		$command = [
			'type' => 'ScrollToComponent'
		];

		if (!empty($aplCommandComponentId)) {
			$command['componentId'] = $aplCommandComponentId;
		}

		if (!empty($aplCommandAlign)) {
			$command['align'] = $aplCommandAlign;
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