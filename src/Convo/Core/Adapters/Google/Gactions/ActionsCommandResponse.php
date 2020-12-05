<?php

namespace Convo\Core\Adapters\Google\Gactions;

use Convo\Core\Adapters\Google\Common\Elements\GoogleActionsElements;
use Convo\Core\Adapters\Google\Common\Intent\GoogleActionsIntentResolver;
use Convo\Core\Adapters\Google\Common\Intent\IActionsIntent;
use Convo\Core\Adapters\Google\Common\Intent\IIntentInputValueDataSpec;
use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse;

class ActionsCommandResponse extends DefaultTextCommandResponse
{
    // todo make smarter responses
    private $_conversationToken = '{ "state": null, "data": {} }';

    private $_value;
    private $_appResponseType;
    private $_visualType;
    private $_confirmationText;

    /** @var GoogleActionsIntentResolver */
    private $_intentResolver;
    /** @var GoogleActionsElements */
    private $_responseElement;

    public function __construct(GoogleActionsIntentResolver $googleActionsIntentResolver, GoogleActionsElements $googleActionsElements)
    {
        parent::__construct();
        $this->_intentResolver = $googleActionsIntentResolver;
        $this->_responseElement = $googleActionsElements;
    }

    public function getPlatformResponse()
    {
        $appResponse = array(
            "conversationToken" => $this->_conversationToken,
            "expectUserResponse" => !$this->shouldEndSession(),
            "expectedInputs" => array(),
        );

        $definedResponse = $this->_defineAppResponse($this->_appResponseType, $appResponse);
        $response = array_merge($appResponse, $definedResponse);

        return json_encode($response);
    }

    /**
     * Defines the final response based on the appResponse type and value.
     *
     * @param $appResponseType
     * @param $response
     * @return array
     */

    private function _defineAppResponse($appResponseType, $response) {
        switch ($appResponseType) {
            case IResponseType::MEDIA_RESPONSE:
                return $this->_prepareMediaResponse($response);
            case IResponseType::BASIC_CARD:
                return $this->_prepareBasicCardResponse($response);
            case IResponseType::CAROUSEL_BROWSE:
                return $this->_prepareCarouselBrowseResponse($response);
            case IResponseType::TABLE_CARD:
                return $this->_prepareTableCardResponse($response);
            case IResponseType::SIGN_IN_RESPONSE:
                return $this->_prepareSignInResponse($response);
            default:
                return $this->_prepareSimpleResponse($response);
        }
    }

    /**
     * Simple textual response for Google Assistant.
     *
     * @param $response
     * @return mixed
     */
    private function _prepareSimpleResponse($response) {
        if ($this->shouldEndSession()) {
            $response["finalResponse"]["richResponse"] = array(
                "items" => array(
                    $this->_responseElement->getSimpleResponseElement("Conversation Response", $this->getTextSsml(),$this->getText())
                )
            );

            $response['expectUserResponse'] = false;
        } else {
            $response["expectedInputs"][0]["inputPrompt"]["richInitialPrompt"]    =  array(
                "items" => array(
                    $this->_responseElement->getSimpleResponseElement("Conversation Response", $this->getTextSsml(),$this->getText())
                )
            );
        }

        $response["expectedInputs"][0]["possibleIntents"] = $this->_intentResolver->resolveIntent(IActionsIntent::TEXT);

        return $response;
    }

    /**
     * Media response for Google Assistant which perform audio playback.
     *
     * @param $response
     * @return mixed
     */
    private function _prepareMediaResponse($response) {
        $text = "Here you go: ";
        $ssml = '<speak>'.$text.'</speak>';
        $suggestions = ["OK, goodbye"];

        $mp3Url = $this->_value;
        $fileNameFromMp3Url = basename($mp3Url);

        $response["expectedInputs"][0]["inputPrompt"]["richInitialPrompt"] = array(
            "items" => array(
                $this->_responseElement->getSimpleResponseElement("Music Response Text", $ssml, $text),
                $this->_responseElement->getMediaResponseElement($mp3Url, $fileNameFromMp3Url)
            ),
            // suggestions must be provided when a request comes from a surface which has  actions.capability.SCREEN_OUTPUT
            // todo brainstorm an idea how to pass the suggestions in the mediaResponse from convo panel
            "suggestions" => $this->_responseElement->getSuggestionsElement($suggestions)
        );

        $response["expectedInputs"][0]["possibleIntents"] = $this->_intentResolver->resolveIntent(IActionsIntent::TEXT);

        return $response;
    }

    /**
     * Will be used later as a visual response for google devices with displays.
     *
     * @param $response
     * @return mixed
     */
    private function _prepareBasicCardResponse($response) {
        $response["expectedInputs"][0]["inputPrompt"]["richInitialPrompt"] = array(
                "items" => array(
                    $this->_responseElement->getSimpleResponseElement("Basic Card Response", $this->getTextSsml(), $this->getText()),
                    // todo map the properties of Basic Card Response from an single object
                    array(
                        "name" => "Basic Card Response",
                        "basicCard" => array(
                        "title" => "Some title",
                        "subtitle" => "Some sub title",
                        "formattedText" => "This is a basic card.  Text in a basic card can include \"quotes\" and\nmost other unicode characters including emoji 📱.  Basic cards also support\nsome markdown formatting like *emphasis* or _italics_, **strong** or\n__bold__, and ***bold itallic*** or ___strong emphasis___ as well as other\nthings like line  \nbreaks",

                        "image" =>
                            array(
                                "url" => "https://icatcare.org/app/uploads/2018/06/Layer-1704-1920x840.jpg",
                                "accessibilityText" => "This is an image of an image"
                            )
                        ,

                        "buttons" => array(
                            array(
                                "title" => "This is a button",
                                "openUrlAction" => array(
                                    "url" => "https://assistant.google.com/"
                                )
                            )
                        )
                    )
                ),
            )
        );

        $response["expectedInputs"][0]["possibleIntents"] = $this->_intentResolver->resolveIntent(IActionsIntent::TEXT);

        return $response;
    }

    /**
     * Will be used later as a visual response for google devices with displays.
     *
     * On click redirects to a website.
     * @param $response
     * @return mixed
     */
    private function _prepareCarouselBrowseResponse($response) {
        $response["expectedInputs"][0]["inputPrompt"]["richInitialPrompt"] = array(
            "items" => array(
                $this->_responseElement->getSimpleResponseElement("Carousel Response", $this->getTextSsml(),$this->getText()),
                // todo map the properties from an external element
                array(
                    "name" => "Carousel Browse Response",
                    "carouselBrowse" => array(
                        "items" => array(
                            array(
                                "title" => "Test",
                                "description" => "Sampe",
                                "footer" => "One more",
                                "openUrlAction" => array("url" => "https://google.com"),
                                "image" => array(
                                    "url" => "https://image.insider.com/5d02563ddaa4821bf4575092?width=1100&format=jpeg&auto=webp",
                                    "accessibilityText" => "Information about Google Assistant"
                                )
                            ),
                            array(
                                "title" => "Test",
                                "description" => "Sampe",
                                "footer" => "One more",
                                "openUrlAction" => array("url" => "https://google.com"),
                                "image" => array(
                                    "url" => "https://i.redd.it/w85fwvz6ttrz.jpg",
                                    "accessibilityText" => "Information about Google Assistant"
                                )
                            )
                        )
                    )
                ),
            )
        );

        $response["expectedInputs"][0]["possibleIntents"] = $this->_intentResolver->resolveIntent(IActionsIntent::TEXT);

        return $response;
    }

    /**
     * Will be used later as a visual response for google devices with displays.
     * This is a sample implementation of the Table Card response.
     * We could use this in our loop element to display data in a table.
     *
     * @param $response
     * @return mixed
     */
    private function _prepareTableCardResponse($response) {
        $response["expectedInputs"][0]["inputPrompt"]["richInitialPrompt"] = array(
            "items" => array(
                $this->_responseElement->getSimpleResponseElement("Table Card Response", $this->getTextSsml(),$this->getText()),
                // todo map the properties from an external element
                array(
                    "name" => "Music Response with Carousel Browse",
                    "tableCard" => array(
                        "title" => "Test",
                        "subtitle" => "Sub Test",
                        "image" => array(
                            "url" => "https://www.humanesociety.org/sites/default/files/styles/768x326/public/2018/08/kitten-440379.jpg?h=f6a7b1af&itok=vU0J0uZR",
                            "accessibilityText" => "Information about Google Assistant"
                        ),
                        "columnProperties" => array(
                            array(
                                "header" => "Header 1",
                                "horizontalAlignment" => "LEADING"
                            ),
                            array(
                                "header" => "Header 2",
                                "horizontalAlignment" => "CENTER"
                            ),
                            array(
                                "header" => "Header 3",
                                "horizontalAlignment" => "TRAILING"
                            )
                        ),
                        "rows" => array(
                            array(
                                "cells" => array(
                                    array("text" => "test 1 1"),
                                    array("text" => "test 2 1"),
                                    array("text" => "test 3 1"),
                                ),
                            ),
                            array(
                                "cells" => array(
                                    array("text" => "test 1 2"),
                                    array("text" => "test 2 2"),
                                    array("text" => "test 3 2"),
                                ),
                            ),
                            array(
                                "cells" => array(
                                    array("text" => "test 1 3"),
                                    array("text" => "test 2 3"),
                                    array("text" => "test 3 3"),
                                ),
                            ),
                            array(
                                "cells" => array(
                                    array("text" => "test 1 4"),
                                    array("text" => "test 2 4"),
                                    array("text" => "test 3 4"),
                                ),
                            )
                        ),
                        "buttons" => array(
                            array(
                                "title" => "This is a button",
                                "openUrlAction" => array(
                                    "url" => "https://assistant.google.com/"
                                )
                            )
                        ),
                    )
                ),
            )
        );

        $response["expectedInputs"][0]["possibleIntents"] = $this->_intentResolver->resolveIntent(IActionsIntent::TEXT);

        return $response;
    }

    private function _prepareSignInResponse($response) {
        $response["expectedInputs"][0]["inputPrompt"]["richInitialPrompt"]["items"][0] = $this->_responseElement->getSimpleResponseElement(
            "Account Linking Prompt",
            $this->getTextSsml(),
            $this->getText()
        );

        $response["expectedInputs"][0]["possibleIntents"] = $this->_intentResolver->resolveIntent(
            IActionsIntent::SIGN_IN,
            IIntentInputValueDataSpec::SIGN_IN_VALUE_SPEC,
            $this->getText()
        );

        return $response;
    }

    /**
     * Can be used as confirmation in Google Assistant.
     * As a request a boolean is sent in arguments along with the text based on the users choice between Yes and No.
     *
     * @param $response
     * @return mixed
     */
    private function _prepareConfirmationResponse($response) {
        $response["expectedInputs"][0]["inputPrompt"]["richInitialPrompt"] = array(
            "items" => array(
                $this->_responseElement->getSimpleResponseElement("Confirmation Response", "PLACEHOLDER", "PLACEHOLDER", IActionsIntent::CONFIRMATION)
            )
        );

        $response["expectedInputs"][0]["possibleIntents"] = $this->_intentResolver->resolveIntent(
            IActionsIntent::CONFIRMATION,
            IIntentInputValueDataSpec::CONFIRMATION_VALUE_SPEC,
            array("requestConfirmationText" => $this->_confirmationText)
        );

        return $response;
    }

    /**
     * Will be used later as a visual response for google devices with displays.
     * This is a sample implementation of the Carousel response.
     * We could use this in our loop element to display data as choosable options in Google Assistant.
     *
     * @param $response
     * @return mixed
     */
    private function _prepareCarouselResponse($response) {

        $response["expectedInputs"][0]["inputPrompt"]["richInitialPrompt"] = array(
            "items" => array(
                array(
                    "simpleResponse" => array(
                        // todo set carousel text
                        "textToSpeech" => "Carousel"
                    )
                )
            )
        );

        $optionValues = array(
            array(
                "optionInfo" => array(
                    "key" => "SELECTION_KEY_GOOGLE_HOME",
                    "synonyms" => array(
                        "synonym of title 1",
                        "synonym of title 2",
                        "synonym of title 3"
                    )
                ),
                "description" => "Item 1 description",
                "image" =>
                    array(
                        "url" => "https://icatcare.org/app/uploads/2018/06/Layer-1704-1920x840.jpg",
                        "accessibilityText" => "This is an image of an image"
                    ),
                "title" => "Item 1 Title"
            ));

        $response["expectedInputs"][0]["possibleIntents"] = $this->_intentResolver->resolveIntent(
            IActionsIntent::OPTION,
            IIntentInputValueDataSpec::OPTION_VALUE_SPEC,
            $optionValues
        );

        return $response;
    }

    /**
     * Sets the response type and the value from an external element.
     *
     * @param $responseType
     * @param $value
     */
    public function prepareResponse($responseType, $value)
    {
        $this->_appResponseType = $responseType;
        $this->_value = $value;
    }

    /**
     * Sets a type for the visual response.
     * @param $visualType
     */
    public function setVisualType($visualType) {
        $this->_visualType = $visualType;
    }

    /**
     * Set text for Confirmation Response
     * @param $confirmationText
     */
    public function setConfirmationText($confirmationText) {
        $this->_confirmationText = $confirmationText;
    }
}
