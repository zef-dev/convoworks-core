<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Alexa\AmazonCommandResponse;
use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Factory\InvalidComponentDataException;
use Convo\Core\Util\IHttpFactory;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Psr\Http\Client\ClientExceptionInterface;

class VoicePinConfirmationDirectiveElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_token;

	public function __construct($properties, $httpFactory)
	{
		parent::__construct($properties);

		$this->_token = $properties['token'] ?? '';
	}

	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
            /** @var AmazonCommandResponse $response */
			$token = $this->evaluateString($this->_token);
            if (!empty($token)) {
                $response->setVoicePinConfirmationDirectiveToken($token);
            }
            $response->prepareResponse(IAlexaResponseType::VOICE_PIN_CONFIRMATION_DIRECTIVE);
		}
	}
}
