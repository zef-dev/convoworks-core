<?php declare(strict_types=1);


namespace Convo\Core\Adapters\Google\Dialogflow;


use Convo\Core\Adapters\Google\Common\Elements\GoogleActionsElements;
use Convo\Core\Adapters\Google\Common\Intent\GoogleActionsSystemIntent;
use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse;
use Convo\Core\Params\IServiceParams;
use Convo\Core\Media\Mp3File;
use Convo\Core\Workflow\IConvoAudioResponse;

class DialogflowCommandResponse extends DefaultTextCommandResponse implements IConvoAudioResponse
{
    private $_conversationToken = '{ "state": null, "data": {} }';
    private $_userStorage = "{\"data\":{}}";

    private $_value;
    private $_suggestions = array();
    private $_responseType = IResponseType::SIMPLE_RESPONSE;

    private $_response;

    /** @var GoogleActionsElements */
    private $_responseElement;

    private $_isSystemIntentInvolved;
    private $_systemIntent;

    /**
     * @var DialogflowCommandRequest
     */
    private $_dialogFlowCommandRequest;

    /**
     * @var IServiceParams
     */
    private $_serviceParams;

    public function __construct($serviceParams, ?DialogflowCommandRequest $dialogFlowCommandRequest = null)
    {
        parent::__construct();
        $this->_serviceParams = $serviceParams;
        $this->_dialogFlowCommandRequest = $dialogFlowCommandRequest;
        $this->_responseElement = new GoogleActionsElements();
        $this->_systemIntent = new GoogleActionsSystemIntent();
    }

    public function addRepromptText($text, $append = false)
    {
        parent::addRepromptText($text, $append);
        $this->_serviceParams->setServiceParam('__reprompt', $this->getRepromptText());
        $this->_serviceParams->setServiceParam('__keepRePrompt', true);
    }

    public function getPlatformResponse()
    {
        $this->_response = $this->_defineAppResponse($this->_responseType);

        $appResponse = array(
            "payload" => array (
                "google" => array(
                    "expectUserResponse" => !$this->shouldEndSession(),
                    "richResponse" => $this->_response
                )
            )
        );

        if ($this->_dialogFlowCommandRequest !== null) {
            $preparedInstallationId = $this->_dialogFlowCommandRequest->getPreparedInstallationId();
            if(!empty($preparedInstallationId)) {
                $userStorageData = [
                    "data" => [
                        "installationId" => $preparedInstallationId
                    ]
                ];

                $this->_userStorage = addslashes(json_encode($userStorageData));
                $appResponse["payload"]["google"]["userStorage"] = $this->_userStorage;
            }
        }

        if ($this->_isSystemIntentInvolved) {
            $this->_isSystemIntentInvolved = false;
            $appResponse["payload"]["google"]["systemIntent"] = $this->_systemIntent->prepareSystemIntent($this->_value);
        }

        if (count($this->_suggestions) > 0) {
            $appResponse["payload"]["google"]["richResponse"]["suggestions"] = $this->_suggestions;
        }

        return json_encode($appResponse);
    }

    /**
     * Defines the final response based on the appResponse type and value.
     *
     * @param $appResponseType
     * @return array
     */
    private function _defineAppResponse($appResponseType) {
        switch ($appResponseType) {
            case IResponseType::MEDIA_RESPONSE:
                return $this->_prepareMediaResponse();
            case IResponseType::LIST:
            case IResponseType::CAROUSEL:
                $this->_isSystemIntentInvolved = true;
                $this->_value = [
                    "ssml" => $this->getTextSsml(),
                    "displayText" => $this->getText(),
                ];
                return $this->_prepareSimpleResponse();
            case IResponseType::BASIC_CARD:
                return $this->_prepareBasicCardResponse();
            case IResponseType::CAROUSEL_BROWSE:
                return $this->_prepareCarouselBrowseResponse();
            default:
                $displayText = " ";
                $ssmlText = "<speak><p>$displayText</p></speak>";
                if (!empty($this->getText())) {
                    $ssmlText = $this->getTextSsml();
                    $displayText = $this->getText();
                }

                $speechText = [
                    "ssml" => $ssmlText,
                    "displayText" => $displayText,
                ];
                $this->_value = $speechText;
                return $this->_prepareSimpleResponse();
        }
    }

    private function _prepareMediaResponse() {
        /**
         * @var Mp3File $mp3Song
         */
        $mp3Song = $this->_value;
        $responseText = "Playing " . $mp3Song->getSongTitle() . " by " . $mp3Song->getArtist();
        return array (
            "items" => array(
                array(
                    "simpleResponse" => array(
                        "textToSpeech" => $responseText,
                        "displayText" => $responseText
                    )
                ),
                array(
                    "mediaResponse" => array(
                        "mediaType" => "AUDIO",
                        "mediaObjects" => array(
                            array(
                                "contentUrl" => $mp3Song->getFileUrl(),
                                "description" => $mp3Song->getSongTitle(),
                                "name" => $mp3Song->getArtist()
                            )
                        )
                    )
                )
            )
        );
    }

    private function _prepareBasicCardResponse() {
        return array (
            "items" => array(
                $this->_responseElement->getSimpleResponseElement("Conversation Response", $this->getTextSsml(),$this->getText()),
                $this->_responseElement->getBasicCardResponseElement($this->_value)
            )
        );
    }

    private function _prepareCarouselBrowseResponse() {
        return array (
            "items" => array(
                $this->_responseElement->getSimpleResponseElement("Conversation Response", $this->getTextSsml(),$this->getText()),
                $this->_responseElement->getCarouselBrowseResponseElement($this->_value)
            )
        );
    }

    private function _prepareSimpleResponse() {
        return array (
            "items" => array(
                $this->_responseElement->getSimpleResponseElement("Conversation Response", $this->_value['ssml'], $this->_value['displayText'])
            )
        );
    }

    /**
     * Sets the response type and the value from an external element.
     *
     * @param $responseType
     * @param $value
     */
    public function prepareResponse($responseType, $value)
    {
        $this->_responseType = $responseType;
        $this->_value = $value;
    }
    
    public function addListItem( $item)
    {
        if ( !isset( $this->_value['list_items'])) {
            $this->_value['list_items']  =   [];
        }
        $this->_value['list_items'][]    =   $item;
    }

    public function setSuggestions($suggestions)
    {
        $this->_suggestions = $suggestions;
    }

    public function getSuggestions()
    {
        return $this->_suggestions;
    }

    public function playSong(Mp3File $song, $offset = 0) : array
    {
        $this->setSuggestions([
            ["title" => "Home"]
        ]);
        $this->prepareResponse(IResponseType::MEDIA_RESPONSE, $song);
        return json_decode($this->getPlatformResponse(), true);
    }

    public function enqueueSong(Mp3File $playingSong, Mp3File $enqueuingSong) : array
    {
        $text = " ";
        return $this->_emptyResponse("<speak><p>$text</p></speak>", $text);
    }

    public function resumeSong(Mp3File $song, $offset) : array
    {
        $text = " ";
        return $this->_emptyResponse("<speak><p>$text</p></speak>", $text);
    }

    public function stopSong() : array
    {
        $text = " ";
        return $this->_emptyResponse("<speak><p>$text</p></speak>", $text);
    }

    public function emptyResponse() : array
    {
        $text = " ";
        return $this->_emptyResponse("<speak><p>$text</p></speak>", $text);
    }

    public function clearQueue() : array
    {
        $text = " ";
        return $this->_emptyResponse("<speak><p>$text</p></speak>", $text);
    }

    private function _emptyResponse($ssmlText, $text) {
        $speechText = [
            "ssml" => $ssmlText,
            "displayText" => $text,
        ];
        $this->prepareResponse(IResponseType::SIMPLE_RESPONSE, $speechText);
        return json_decode($this->getPlatformResponse(), true);
    }
}
