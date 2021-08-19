<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;

class AplBackstackGoBackCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{
	private $_useAplBackType;
	private $_aplBackType;
	private $_aplBackValue;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_useAplBackType = $properties['use_apl_back_type'] ?? false;
		$this->_aplBackType = $properties['apl_back_type'];
		$this->_aplBackValue = $properties['apl_back_value'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$useAplBackType = $this->evaluateString($this->_useAplBackType);
		$aplBackType = $this->evaluateString($this->_aplBackType);
		$aplBackValue = $this->evaluateString($this->_aplBackValue);

		$command = [
			'type' => 'Back:GoBack',
		];

		if ($useAplBackType) {
			if ($aplBackType === 'count' || $aplBackType === 'index') {
				if (!is_numeric($aplBackValue)) {
					throw new InvalidComponentDataException('When using count or index the APL Back value has to be a number.');
				}
			}

			$command['backType'] = $aplBackType;
			$command['backValue'] = ($aplBackType === 'count' || $aplBackType === 'index') ?
				intval($aplBackValue) : $aplBackValue;
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