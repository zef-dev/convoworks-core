<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use PHPUnit\Framework\InvalidDataProviderException;

class AplSetValueCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{
	private $_aplCommandComponentId;
	private $_aplCommandProperty;
	private $_aplCommandValue;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandComponentId = $properties['command_component_id'];
		$this->_aplCommandProperty = $properties['command_property'];
		$this->_aplCommandValue = $properties['command_value'];
	}

	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		$aplCommandComponentId = $this->evaluateString($this->_aplCommandComponentId);
		$aplCommandProperty = $this->evaluateString($this->_aplCommandProperty);
		$aplCommandValue = $this->evaluateString($this->_aplCommandValue);

		if (!empty($aplCommandProperty) || !empty($aplCommandValue)) {
			throw new InvalidDataProviderException('You have to provide an property and a value to the SetValue Command');
		}

		$command = [
			'type' => 'SetValue',
			'property' => $aplCommandProperty,
			'value' => $aplCommandValue,
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