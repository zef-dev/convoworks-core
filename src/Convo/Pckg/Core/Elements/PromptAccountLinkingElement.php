<?php

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class PromptAccountLinkingElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
    public function __construct($properties)
    {
        parent::__construct($properties);
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest'))
        {
            /** @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse $response */
            $response->promptAccountLinking();
            $response->setShouldEndSession(true);

            throw new \Convo\Core\SessionEndedException();
        }
        else if (is_a($request, '\Convo\Core\Adapters\Gactions\ActionsCommandRequest'))
        {
            /** @var \Convo\Core\Adapters\Google\Gactions\ActionsCommandResponse $response */
            $response->prepareResponse(
                \Convo\Core\Adapters\Google\Common\IResponseType::SIGN_IN_RESPONSE,
                null
            );
            $response->setShouldEndSession(true);

            throw new \Convo\Core\SessionEndedException();
        }
    }
}
