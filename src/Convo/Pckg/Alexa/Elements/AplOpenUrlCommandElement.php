<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Factory\InvalidComponentDataException;
use PHPUnit\Framework\InvalidDataProviderException;

class AplOpenUrlCommandElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Pckg\Alexa\Workflow\IAplCommandElement
{

	private $_aplCommandSource;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_aplCommandSource 	= $properties['command_source_url'];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$aplCommandSource = $this->evaluateString($this->_aplCommandSource);

		$command = [
			'type' => 'OpenURL',
			'source' => $aplCommandSource,
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