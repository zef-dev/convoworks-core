<?php


namespace Convo\Pckg\Alexa\Elements;


use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class AplExecuteCommandsElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_templateToken;
	/**
	 * @var \Convo\Pckg\Alexa\Workflow\IAplCommandElement[]
	 */
	private $_aplCommands = [];

	public function __construct( $properties)
	{
		parent::__construct($properties);

		$this->_templateToken = $properties['name'];
		if ( isset($properties['apl_commands'])) {
			foreach ( $properties['apl_commands'] as $element) {
				$this->addAplCommand($element);
			}
		}
	}

	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		$aplToken = $this->evaluateString($this->_templateToken);
		if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
		{
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
			/* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
			if ($request->getIsAplSupported()) {
				$response->prepareResponse(IAlexaResponseType::APL_RESPONSE);

				$response->setAplToken($aplToken);
				foreach ($this->_aplCommands as $aplCommand) {
					$aplCommand->read($request, $response);
				}
			}
		}
	}

	public function addAplCommand(\Convo\Pckg\Alexa\Workflow\IAplCommandElement $element)
	{
		$this->_aplCommands[] = $element;
		$this->addChild($element);
	}
}