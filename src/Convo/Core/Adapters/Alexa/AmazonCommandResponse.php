<?php

declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Media\Mp3File;
use Convo\Core\Workflow\IConvoAudioResponse;

class AmazonCommandResponse extends \Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse implements IConvoAudioResponse
{
    private $_mp3url;
    private $_offsetMilliseconds;

    private $_currentSongToken;
    private $_previousSongToken;

    private $_mode;

    private $_dataList;
    private $_selectedOption;
    private $_dataCard;

    private $_backButton;

    private $_responseType;
    private $_response;

    private $_serviceAmazonConfig = null;
    private $_isDisplaySupported = false;

    private $_sendAccountLinkingCard = false;

    /**
     * @var AmazonCommandRequest
     */
    private $_amazonCommandRequest;

    private $_platformResponse = [];

    private $_audioResponse = [];

    private $_metadata;

    private $_texts			=	array();
    private $_reprompts		=	array();

    public function __construct(AmazonCommandRequest $amazonCommandRequest)
    {
        parent::__construct();
        $this->_amazonCommandRequest    =    $amazonCommandRequest;
    }

    public function setUrl($text)
    {
        $this->_mp3url = $text;
    }

    public function getUrl()
    {
        return $this->_mp3url;
    }

    public function setMetadata($metadata) {
        $this->_metadata = $metadata;
    }

    public function getMetadata() {
        return $this->_metadata;
    }

    public function getOffsetMilliseconds()
    {
        return $this->_offsetMilliseconds;
    }

    public function setOffsetMilliseconds($offset)
    {
        $this->_offsetMilliseconds = $offset;
    }

    public function getCurrentSongToken()
    {
        return $this->_currentSongToken;
    }

    public function setCurrentSongToken($token)
    {
        $this->_currentSongToken = $token;
    }

    public function getPreviousSongToken()
    {
        return $this->_previousSongToken;
    }

    public function setPreviousSongToken($token)
    {
        $this->_previousSongToken = $token;
    }

    public function setMode($mode)
    {
        $this->_mode = $mode;
    }
    public function getMode()
    {
        return $this->_mode;
    }

    public function setDataList($dataList)
    {
        $this->_dataList = $dataList;
    }
    public function getDataList()
    {
        return $this->_dataList;
    }

    public function setDataCard($dataCard)
    {
        $this->_dataCard = $dataCard;
    }
    public function getDataCard()
    {
        return $this->_dataCard;
    }

    public function setBackButton($backButton)
    {
        $this->_backButton = $backButton;
    }
    public function getBackButton()
    {
        return $this->_backButton;
    }

    public function getSelectedOption()
    {
        return $this->_selectedOption;
    }

    public function setSelectedOption($token)
    {
        $this->_selectedOption = $token;
    }

    public function setServiceAmazonConfig($serviceAmazonConfig) {
        $this->_serviceAmazonConfig = $serviceAmazonConfig;
    }

    public function setIsDisplaySupported($isDisplaySupported) {
        $this->_isDisplaySupported = $isDisplaySupported;
    }

    public function promptAccountLinking()
    {
        $this->_sendAccountLinkingCard = true;
    }

    public function prepareResponse($responseType)
    {
        $this->_responseType = $responseType;
    }

    private function _defineAppResponse($appResponseType) {
        switch ($appResponseType) {
            case IAlexaResponseType::MEDIA_RESPONSE:
                $this->_platformResponse = $this->_prepareMediaResponse();
                break;
            case IAlexaResponseType::LIST_RESPONSE:
                $this->_platformResponse = $this->_prepareListResponse();
                break;
            case IAlexaResponseType::CARD_RESPONSE:
                $this->_platformResponse = $this->_prepareCardResponse();
                break;
            case IAlexaResponseType::EMPTY_RESPONSE:
                $this->_platformResponse = $this->_prepareEmptySessionEndResponse();
                break;
            default:
                $this->_platformResponse = $this->_prepareSimpleResponse();
                break;
        }
    }

    public function prepareItems() {
        $listItems = array();
        foreach ($this->_dataList['list_items'] as $listItem) {
            $obj = array(
                'token' => $listItem['list_item_key'],
                'image' => [
                    'sources' => [
                        [
                            'url' => $listItem['list_item_image_url'],
                        ]
                    ],
                    'contentDescription' => $listItem['list_item_image_text']
                ],
                'textContent' => [
                    'primaryText' => [
                        'type' => 'RichText',
                        'text' => $listItem['list_item_title']
                    ],
                    'secondaryText' => [
                        'type' => 'PlainText',
                        'text' => $listItem['list_item_description_1']
                    ],
                    'tertiaryText' => [
                        'type' => 'PlainText',
                        'text' => $listItem['list_item_description_2']
                    ]
                ]
            );

            array_push($listItems, $obj);
        }

        return $listItems;
    }

    private function _prepareListResponse() {

        $data = array(
            'version' => '1.0',
            'response' => array(),
        );

        $this->_logger->debug('List template ['.print_r($this->_dataList['list_template'], true).']');

        if (strtolower($this->_dataList['list_template']) == 'list') {
            $listType = 'ListTemplate1';
        }
        else if (strtolower($this->_dataList['list_template']) == 'carousel') {
            $listType = 'ListTemplate2';
        }
        else {
            $listType = 'ListTemplate1';
        }

        if ((!empty($this->_dataList)) && $this->_selectedOption == null) {
            $data['response']['outputSpeech']['type'] = 'SSML';
            $data['response']['outputSpeech']['ssml'] = $this->getTextSsml();
            $data['response']['directives'] = [];
            $data['response']['directives'][] = [
                'type' => 'Display.RenderTemplate',
                'template' => [
                    'type' => $listType,
                    'title' => $this->_dataList['list_title'],
                    'backButton' => 'HIDDEN',
                    'token' => 0,
                    'listItems' => $this->prepareItems()
                ]
            ];
        }

        return $data;
    }

    private function _prepareCardResponse() {

        $data = array(
            'version' => '1.0',
            'response' => array(),
        );
        $this->_logger->debug('Card ['.print_r($this->_dataCard, true).']');
        $this->_logger->debug('Item selected, selected Option= ' . '['.$this->_selectedOption.']');
        $this->_logger->debug('Back Button value ' . '['.$this->_backButton.']');
        if (!empty($this->_dataCard)) {

            $title = $this->_dataCard['data_item_title'];
            $image = $this->_dataCard['data_item_image_url'];
            $contentDescription = $this->_dataCard['data_item_image_text'];
            $primaryText = $this->_dataCard['data_item_description_1'];
            $secondaryText = $this->_dataCard['data_item_description_2'];

            $data['response']['outputSpeech']['type'] = 'SSML';
            $data['response']['outputSpeech']['ssml'] = $this->getTextSsml();
            $data['response']['directives'] = [];
            $data['response']['directives'][] = [
                'type' => 'Display.RenderTemplate',
                'template' => [
                    'type' => 'BodyTemplate2',
                    'title' => $title,
                    'token' => $this->_selectedOption !== null ? $this->_selectedOption : 0,
                    'backButton' => $this->_backButton,
                    'image' => [
                        'contentDescription' => $contentDescription,
                        'sources' => [
                            [
                                'url' => $image
                            ]
                        ]
                    ],
                    'textContent' => [
                        'primaryText' => [
                            'text' => $primaryText,
                            'type' => 'RichText'
                        ],
                        'secondaryText' => [
                            'text' => $secondaryText,
                            'type' => 'PlainText'
                        ]
                    ]
                ]
            ];
        }

        return $data;
    }

    private function _prepareMediaResponse() {

        $data = array(
            'version' => '1.0',
            'response' => array(),
        );

        if ($this->_mp3url !== null || $this->_mode !== null) {
            $this->_logger->debug("MP3 URL [$this->_mp3url][$this->_mode]");

            if ($this->_mode === 'play') {
                $this->_logger->debug('_offsetMilliseconds= ' . $this->_offsetMilliseconds);
                // AUDIO TEST
                $data['response']['directives'] = [];
                $data['response']['directives'][] = [
                    'type' => 'AudioPlayer.Play',
                    'playBehavior' => 'REPLACE_ALL',
                    'audioItem' => [
                        'stream' => [
                            'token' => $this->_currentSongToken,
                            'url' => $this->_mp3url,
                            'offsetInMilliseconds' => $this->_offsetMilliseconds ?? 0
                        ]
                    ]
                ];

                if (!empty($this->_metadata)) {
                    $data['response']['directives'][0]['audioItem']['metadata'] = [
                        "title" => $this->_metadata["song"],
                        "subtitle" => $this->_metadata["artist"]
                    ];
                }
            } else if ($this->_mode === 'stop') {
                $data['response']['directives'][] = [
                    'type' => 'AudioPlayer.Stop'
                ];
            } else if ($this->_mode === 'enqueue') {
                $data['response']['directives'] = [];
                $data['response']['directives'][] = [
                    'type' => 'AudioPlayer.Play',
                    'playBehavior' => 'ENQUEUE',
                    'audioItem' => [
                        'stream' => [
                            'token' => $this->_currentSongToken,
                            'expectedPreviousToken' => $this->_previousSongToken,
                            'url' => $this->_mp3url,
                            'offsetInMilliseconds' => 0
                        ]
                    ]
                ];

                if (!empty($this->_metadata)) {
                    $data['response']['directives'][0]['audioItem']['metadata'] = [
                        "title" => $this->_metadata["song"],
                        "subtitle" => $this->_metadata["artist"]
                    ];
                }
            }
            else if ($this->_mode === 'clearEnqueue') {
                $data['response']['directives'][] = [
                    'type' => 'AudioPlayer.ClearQueue',
                    'clearBehavior' => 'CLEAR_ENQUEUED'
                ];
                //$data['response']['shouldEndSession'] = 'true';
            } else if ($this->_mode === 'other') {
                //unset($data);
                $data = [
                    'version' => '1.0',
                    'response' => (object) [],
                ];
            }
        }

        return $data;
    }

    private function _prepareSimpleResponse() {
        $data = array(
            'version' => '1.0',
            'response' => array(
                'outputSpeech' => array(
                    "type" => 'SSML',
                    "ssml" => '<speak></speak>',
                ),
                'shouldEndSession' => $this->shouldEndSession(),
            ),
        );
        if ($this->getText() != null) {
            $data['response']['outputSpeech']['type'] = 'SSML';
            $data['response']['outputSpeech']['ssml'] = $this->getTextSsml();
            if (!empty($this->getRepromptText())) {
                $data['response']['reprompt']['outputSpeech']['type'] = 'SSML';
                $data['response']['reprompt']['outputSpeech']['ssml'] = $this->getRepromptTextSsml();
            }

            if ($this->_sendAccountLinkingCard) {
                $data['response']['card'] = ['type' => 'LinkAccount'];
            }

            $isAutoDisplay = isset($this->_serviceAmazonConfig['auto_display']) ? $this->_serviceAmazonConfig['auto_display'] : false;

            if ($this->_isDisplaySupported && $isAutoDisplay) {
                $data['response']['card'] = [
                    'type' => 'Simple',
                    'title' => isset($this->_serviceAmazonConfig['invocation']) ? ucwords($this->_serviceAmazonConfig['invocation']) : '',
                    'content' => $this->getText()
                ];
            }
        }

        return $data;
    }

    private function _prepareEmptySessionEndResponse() {
        return [
            "version" => "1.0",
            "response" => [
                "shouldEndSession" => true,
                "type" => "_DEFAULT_RESPONSE"
            ]
        ];
    }

    public function setAudioResponse($audioResponse) {
        $this->_audioResponse = $audioResponse;
    }

    public function getAudioResponse() {
        return $this->_audioResponse;
    }

    // SPEECH
    public function addText( $text, $append = false)
    {
        if ($append && count($this->_texts) > 0) {
            $this->_appendText($text, $this->_texts);
        } else {
            $this->_texts[]	=	'<p>'.$this->_clearWrappers( $text).'</p>';
        }
    }

    public function getText()
    {
        return preg_replace('/\s\s+/', ' ', strip_tags( $this->getTextSsml()));
    }

    public function getTextSsml() {
        if (count($this->_texts) > 0) {
            $last = count($this->_texts) - 1;

            if (stripos($this->_texts[$last], '</p>') === false) {
                $this->_texts[$last] = $this->_texts[$last].'</p>';
            }
        }

        return '<speak>'.preg_replace('/\s\s+/', ' ', implode( " ", $this->_texts)).'</speak>';
    }

    // REPROMPT
    public function addRepromptText( $text, $append = false)
    {
        if ($append && count($this->_reprompts) > 0) {
            $this->_appendText($text, $this->_reprompts);
        } else {
            $this->_reprompts[]	=	'<p>'.$this->_clearWrappers( $text).'</p>';
        }
    }

    public function getRepromptText() {
        return strip_tags( $this->getRepromptTextSsml());
    }

    public function getRepromptTextSsml() {
        return '<speak>'.implode( " ", $this->_reprompts).'</speak>';
    }

    public function addEmotionText($emotion, $intensity, $emotionText, $append = false) {
        if ($append && count($this->_texts) > 0) {
            $this->_appendText("<amazon:emotion name='$emotion' intensity='$intensity'>" . $this->_clearWrappers($emotionText) . "</amazon:emotion>", $this->_texts);
        } else {
            $this->_texts[] = "<p><amazon:emotion name='$emotion' intensity='$intensity'>" . $this->_clearWrappers($emotionText) . "</amazon:emotion></p>";
        }
    }

    public function addDomainText($domain, $emotionText, $append = false) {
        if ($append && count($this->_texts) > 0) {
            $this->_appendText("<amazon:domain name='$domain'>" . $this->_clearWrappers($emotionText) . "</amazon:domain>", $this->_texts);
        } else {
            $this->_texts[] = "<p><amazon:domain name='$domain'>" . $this->_clearWrappers($emotionText) . "</amazon:domain></p>";
        }
    }

    public function addEmotionRepromptText($emotion, $intensity, $emotionText, $append = false) {
        if ($append && count($this->_reprompts) > 0) {
            $this->_appendText("<amazon:emotion name='$emotion' intensity='$intensity'>" . $this->_clearWrappers($emotionText) . "</amazon:emotion>", $this->_reprompts);
        } else {
            $this->_reprompts[] = "<p><amazon:emotion name='$emotion' intensity='$intensity'>" . $this->_clearWrappers($emotionText) . "</amazon:emotion></p>";
        }
    }

    public function addDomainRepromptText($domain, $emotionText, $append = false) {
        if ($append && count($this->_reprompts) > 0) {
            $this->_appendText("<amazon:domain name='$domain'>" . $this->_clearWrappers($emotionText) . "</amazon:domain>", $this->_reprompts);
        } else {
            $this->_reprompts[] = "<p><amazon:domain name='$domain'>" . $this->_clearWrappers($emotionText) . "</amazon:domain></p>";
        }
    }

    private function _appendText($text, &$array)
    {
        $preceding = array_pop($array);
        $preceding = "<p>".$this->_clearWrappers($preceding).' '.$this->_clearWrappers($text)."</p>";
        $array[] = $preceding;
    }

    private function _clearWrappers( $text) {
        $text	=	str_ireplace( '<speak>', '', $text);
        $text	=	str_ireplace( '</speak>', '', $text);
        $text	=	str_ireplace( '<p>', '', $text);
        $text	=	str_ireplace( '</p>', '', $text);
        return $text;
    }

    public function getPlatformResponse() {
        $this->_defineAppResponse($this->_responseType);
        return $this->_platformResponse;
    }

    public function playSong(Mp3File $song, $offset = 0) : array
    {
        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMetadata($song->getMetaData());
        $this->setOffsetMilliseconds($offset);
        $this->setUrl($song->getFileUrl());
        $this->setCurrentSongToken(md5($song->getFileUrl()));
        $this->setMode("play");

        return $this->getPlatformResponse();
    }

    public function enqueueSong(Mp3File $playingSong, Mp3File $enqueuingSong) : array
    {
        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setOffsetMilliseconds(0);
        $this->setUrl($enqueuingSong->getFileUrl());
        $this->setMetadata($enqueuingSong->getMetaData());
        $this->setPreviousSongToken(md5($playingSong->getFileUrl()));
        $this->setCurrentSongToken(md5($enqueuingSong->getFileUrl()));

        $this->setMode("enqueue");

        return $this->getPlatformResponse();
    }

    public function resumeSong(Mp3File $song, $offset) : array
    {
        return $this->playSong($song, $offset);
    }

    public function stopSong() : array
    {
        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMode("stop");
        return $this->getPlatformResponse();
    }

    public function emptyResponse() : array
    {
        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMode("other");
        return $this->getPlatformResponse();
    }

    public function clearQueue() : array
    {
        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMode("clearEnqueue");
        return $this->getPlatformResponse();
    }
}
