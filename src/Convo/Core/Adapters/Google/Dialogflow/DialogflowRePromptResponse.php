<?php


namespace Convo\Core\Adapters\Google\Dialogflow;


use Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse;
use Convo\Core\Params\IServiceParams;

class DialogflowRePromptResponse extends DefaultTextCommandResponse
{
    /**
     * @var IServiceParams
     */
    private $_serviceParams;

    public function __construct(IServiceParams $serviceParams)
    {
        parent::__construct();
        $this->_serviceParams = $serviceParams;
    }

    /**
     * @inheritDoc
     */
    public function getPlatformResponse()
    {
        $text = $this->getText();
        $appResponse = array(
            "payload" => array (
                "google" => array(
                    "expectUserResponse" => !$this->shouldEndSession(),
                    "richResponse" =>   array(
                        "items" => [
                            array (
                                "simpleResponse" => array(
                                    "textToSpeech" => "<speak><p>$text</p></speak>",
                                    "displayText" => $text
                                )
                            )
                        ]
                    )
                )
            )
        );

        return json_encode($appResponse);
    }

    public function getText()
    {
        return $this->_serviceParams->getServiceParam("__reprompt");
    }

    public function shouldEndSession()
    {
        return $this->_serviceParams->getServiceParam("__finalReprompt");
    }
}
