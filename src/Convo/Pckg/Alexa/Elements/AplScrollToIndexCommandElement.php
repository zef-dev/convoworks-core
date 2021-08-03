<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplScrollToIndexCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	private $_aplCommandComponentId;
	private $_aplCommandAlign;
	private $_aplCommandIndex;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandComponentId 	= $properties['command_component_id'];
		$this->_aplCommandAlign 		= $properties['command_align'];
		$this->_aplCommandIndex 		= $properties['command_index'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandComponentId = $this->evaluateString($this->_aplCommandComponentId);
		$aplCommandAlign = $this->evaluateString($this->_aplCommandAlign);
		$aplCommandIndex = $this->evaluateString($this->_aplCommandIndex);

		if (!is_numeric($aplCommandIndex)) {
			throw new InvalidComponentDataException('The provided index is not valid.');
		}

		$command = [
			'type' => 'ScrollToIndex',
			'index' => intval($aplCommandIndex),
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