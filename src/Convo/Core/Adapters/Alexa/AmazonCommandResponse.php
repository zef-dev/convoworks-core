<?php

declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Media\IAudioFile;
use Convo\Core\Media\IRadioStream;
use Convo\Core\Workflow\IConvoAudioResponse;
use Convo\Core\Workflow\IConvoRadioStreamResponse;
use Convo\Core\Workflow\IMediaType;

class AmazonCommandResponse extends \Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse implements IConvoAudioResponse, IConvoRadioStreamResponse
{
    private $_mp3url;
    private $_offsetMilliseconds;

    private $_videoUrl = null;
    private $_videoTitle = null;
    private $_videoSubtitle = null;

    private $_currentSongToken;
    private $_previousSongToken;

    private $_mode;

    private $_dataList;
    private $_selectedOption;
    private $_dataCard;

	private $_aplToken;
	private $_aplDefinition;

	private $_salesDirective;

	private $_voicePinConfirmationDirectiveToken;

	private $_aplCommandToken;
	private $_aplCommands = [];

    private $_shouldDelegateToAlexaDialog = false;
    private $_dialogDelegateDirectiveUpdatedIntent = [];

    private $_backButton;

    private $_responseType;
    private $_response;

    private $_serviceAmazonConfig = null;
    private $_isDisplaySupported = false;

    private $_sendAccountLinkingCard = false;
    private $_sendPermissionsConsentCard = false;
    private $_permissionsToAskFor = [];

    private $_shouldSendStandardCard = false;
    private $_standardCardTitle = '';
    private $_standardCardText = '';
    private $_standardCardSmallImageURL = null;
    private $_standardCardLargeImageURL = null;

    private $_shouldSendSimpleCard = false;
    private $_simpleCardTitle = '';
    private $_simpleCardContent = '';

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

	public function setAplToken($aplToken)
	{
		$this->_aplToken = $aplToken;
	}

	public function getAplToken()
	{
		return $this->_aplToken;
	}

	public function setAplDefinition($aplDefinition)
	{
		$this->_aplDefinition = $aplDefinition;
	}

	public function setSalesDirective($salesDirective)
	{
		$this->_salesDirective = $salesDirective;
	}

    public function setVoicePinConfirmationDirectiveToken($voicePinConfirmationDirectiveToken)
    {
        $this->_voicePinConfirmationDirectiveToken = $voicePinConfirmationDirectiveToken;
    }

	public function getAplDefinition()
	{
		return $this->_aplDefinition;
	}

	public function setAplCommandToken($aplCommandToken)
	{
		$this->_aplCommandToken = $aplCommandToken;
	}

	public function setAplCommandComponentId($aplCommandComponentId)
	{
		$this->_aplCommandComponentId = $aplCommandComponentId;
	}

	public function setAplCommandProperty($aplCommandProperty)
	{
		$this->_aplCommandProperty = $aplCommandProperty;
	}

	public function setAplCommandValue($aplCommandValue)
	{
		$this->_aplCommandValue = $aplCommandValue;
	}

    public function addListItem( $item)
    {
        if ( !isset( $this->_dataList['list_items'])) {
            $this->_dataList['list_items']  =   [];
        }
        $this->_dataList['list_items'][]    =   $item;
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

	public function setPermissionsToAskFor($permissionsToAskFor) {
		$this->_permissionsToAskFor = $permissionsToAskFor;
	}

    public function promptAccountLinking()
    {
        $this->_sendAccountLinkingCard = true;
    }

    public function sendStandardCard($title, $text, $smallImageURL = null, $largeImageURL = null)
    {
        $this->_shouldSendStandardCard = true;
        $this->_standardCardTitle = $title;
        $this->_standardCardText = $text;
        $this->_standardCardSmallImageURL = $smallImageURL;
        $this->_standardCardLargeImageURL = $largeImageURL;
    }

    public function sendSimpleCard($title, $content)
    {
        $this->_shouldSendSimpleCard = true;
        $this->_simpleCardTitle = $title;
        $this->_simpleCardContent = $content;
    }

	public function promptPermissionsConsent()
	{
		$this->_sendPermissionsConsentCard = true;
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
            case IAlexaResponseType::VIDEO_RESPONSE:
                $this->_platformResponse = $this->_prepareVideoResponse();
                break;
			case IAlexaResponseType::APL_RESPONSE:
				$this->_platformResponse = $this->_prepareAplResponse();
				break;
			case IAlexaResponseType::SALES_DIRECTIVE:
				$this->_platformResponse = $this->_prepareSalesDirectiveResponse();
				break;
            case IAlexaResponseType::DIALOG_DELEGATE_DIRECTIVE:
                $this->_platformResponse = $this->_prepareDialogDelegateResponse();
                break;
			case IAlexaResponseType::VOICE_PIN_CONFIRMATION_DIRECTIVE:
				$this->_platformResponse = $this->_prepareVoicePinConfirmationDirectiveResponse();
				break;
            default:
                $this->_platformResponse = $this->_prepareSimpleResponse();
                break;
        }
    }

    public function prepareItems() {
        $listItems = array();
		if (strtolower($this->_dataList['list_template']) == 'list') {
			$imageSourceProperty = 'imageThumbnailSource';
		}
		else if (strtolower($this->_dataList['list_template']) == 'carousel') {
			$imageSourceProperty = 'imageSource';
		}
		else {
			$imageSourceProperty = 'imageThumbnailSource';
		}
        foreach ($this->_dataList['list_items'] as $listItem) {
            $listItemObject = array(
				'primaryText' => $listItem['list_item_title'],
				$imageSourceProperty => $listItem['list_item_image_url'],
                'primaryAction' => [
                	[
						"type" => "SendEvent",
						"arguments" => [
							[
								'selected_list_item_key' =>  $listItem['list_item_key']
							]
						]
					]
				]
            );

            array_push($listItems, $listItemObject);
        }

        return $listItems;
    }

	public function addAplCommand($aplCommand) {
		if ( empty( $this->_aplCommands)) {
			$this->_aplCommands = [];
		}
		$this->_aplCommands[] = $aplCommand;
	}

    public function delegate($intent = []) {
        $this->_shouldDelegateToAlexaDialog = true;
        $this->_dialogDelegateDirectiveUpdatedIntent = $intent;
    }

    private function _prepareListResponse() {

        $data = array(
            'version' => '1.0',
            'response' => array(),
        );

        $this->_logger->debug('List template ['.print_r($this->_dataList['list_template'], true).']');

        if (strtolower($this->_dataList['list_template']) == 'list') {
            $listType = 'AlexaTextList';
        }
        else if (strtolower($this->_dataList['list_template']) == 'carousel') {
            $listType = 'AlexaImageList';
        }
        else {
            $listType = 'AlexaTextList';
        }

		$data['response']['shouldEndSession'] = $this->shouldEndSession();

        if ((!empty($this->_dataList)) && $this->_selectedOption == null) {
            $data['response']['outputSpeech']['type'] = 'SSML';
            $data['response']['outputSpeech']['ssml'] = $this->getTextSsml();
            $data['response']['directives'] = [];
            $data['response']['directives'][] = [
                'type' => 'Alexa.Presentation.APL.RenderDocument',
				'token' => 'itemsListToken',
				'document' => [
					'type' => 'APL',
					'version' => '1.6',
					'theme' => 'dark',
					'extensions' => [
						[
							'name' => 'Back',
							'uri' => 'aplext:backstack:10'
						]
					],
					'settings' => [
						'Back' => [
							'backstackId' => 'itemsList'
						]
					],
					'import' => [
						[
							'name' => 'alexa-layouts',
							'version' => '1.3.0'
						]
					],
					'mainTemplate' => [
						'parameters' => [
							'payload'
						],
						'items' => [
							[
								'type' => $listType,
								'headerTitle' => '${payload.textListData.title}',
								'headerBackButton'=> false,
								'listItems' => '${payload.textListData.listItems}',
								'touchForward' => true,
								'listId'=> "selectionItemsList"
							]
						]
					],
				],
				'datasources' => [
					'textListData' => [
						'type' => 'object',
						'objectId' => 'textListSource',
						'title' => $this->_dataList['list_title'],
						'logoUrl' => 'https://d2o906d8ln7ui1.cloudfront.net/images/templates_v3/logo/logo-modern-botanical-white.png',
						'listItems' => $this->prepareItems()
					]
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
		$data['response']['shouldEndSession'] = $this->shouldEndSession();
        if (!empty($this->_dataCard)) {

            $title = $this->_dataCard['data_item_title'];
            $subtitle = $this->_dataCard['data_item_subtitle'];
            $image = $this->_dataCard['data_item_image_url'];
            $contentDescription = $this->_dataCard['data_item_image_text'];
            $primaryText = $this->_dataCard['data_item_description_1'];
            $secondaryText = $this->_dataCard['data_item_description_2'];

            $data['response']['outputSpeech']['type'] = 'SSML';
            $data['response']['outputSpeech']['ssml'] = $this->getTextSsml();
            $data['response']['directives'] = [];
            $data['response']['directives'][] = [
                'type' => 'Alexa.Presentation.APL.RenderDocument',
                'token' => $this->getSelectedOption(),
                'document' => [
                    'type' => 'APL',
                    'version' => '1.6',
					'import' => [
						[
							'name' => 'alexa-layouts',
							'version' => '1.3.0'
						]
					],
					'mainTemplate' => [
						'parameters' => [
							'payload'
						],
						'items' => [
							[
								'type' => "AlexaDetail",
								'id' => "itemDetails",
								'detailType' => 'generic',
								'detailImageAlignment' => 'right',
								'headerTitle' => '${payload.detailImageRightData.title}',
								'headerSubtitle' => '${payload.detailImageRightData.subtitle}',
								'headerBackButton' => false,
								'imageBlurredBackground' => false,
								'imageScale' => 'best-fill',
								'imageAspectRatio' => 'square',
								'imageAlignment' => 'right',
								'imageSource' => '${payload.detailImageRightData.image.sources[0].url}',
								'imageCaption' => '${payload.detailImageRightData.image.contentDescription}',
								'primaryText' => '${payload.detailImageRightData.textContent.primaryText.text}',
								'secondaryText' => '${payload.detailImageRightData.textContent.secondaryText.text}',
								'theme' => 'dark'
							]
						]
					],
                ],
				'datasources' => [
					'detailImageRightData' => [
						'type' => 'object',
						'objectId' => 'detailImageRightSample',
						'title' => $title,
						'subtitle' => $subtitle,
						'image' => [
							'contentDescription' => $contentDescription,
							'smallSourceUrl' => null,
							'largeSourceUrl' => null,
							'sources' => [
								[
									'url' => $image,
									'size' => 'large'
								]
							]
						],
						'textContent' => [
							'primaryText' => [
								'type' => 'PlainText',
								'text' => $primaryText
							],
							'secondaryText' => [
								'type' => 'PlainText',
								'text' => $secondaryText
							]
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

        if ($this->getText() != null) {
            $data['response']['outputSpeech']['type'] = 'SSML';
            $data['response']['outputSpeech']['ssml'] = $this->getTextSsml();
        }

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
                        "subtitle" => $this->_metadata["artist"],
                    ];

                    if ( isset( $this->_metadata["art"]) && !empty( $this->_metadata["art"])) {
                        $data['response']['directives'][0]['audioItem']['metadata']['art']
                        =   [ "sources" => [[ "url" => $this->_metadata["art"]]]];
                    }

                    if ( isset( $this->_metadata["backgroundImage"]) && !empty( $this->_metadata["backgroundImage"])) {
                        $data['response']['directives'][0]['audioItem']['metadata']['backgroundImage']
                        =   [ "sources" => [[ "url" => $this->_metadata["backgroundImage"]]]];
                    }
                }
            } else if ($this->_mode === 'stop') {
                $data['response']['shouldEndSession'] = true;
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
                        "subtitle" => $this->_metadata["artist"],
                    ];

                    if ( isset( $this->_metadata["art"]) && !empty( $this->_metadata["art"])) {
                        $data['response']['directives'][0]['audioItem']['metadata']['art']
                        =   [ "sources" => [[ "url" => $this->_metadata["art"]]]];
                    }

                    if ( isset( $this->_metadata["backgroundImage"]) && !empty( $this->_metadata["backgroundImage"])) {
                        $data['response']['directives'][0]['audioItem']['metadata']['backgroundImage']
                        =   [ "sources" => [[ "url" => $this->_metadata["backgroundImage"]]]];
                    }
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

    private function _prepareVideoResponse() {
        $data = array(
            'version' => '1.0',
            'response' => array()
        );

        if (!empty($this->_videoUrl)) {
            if ($this->getText() != null) {
                $data['response']['outputSpeech']['type'] = 'SSML';
                $data['response']['outputSpeech']['ssml'] = $this->getTextSsml();

                $isAutoDisplay = isset($this->_serviceAmazonConfig['auto_display']) ? $this->_serviceAmazonConfig['auto_display'] : false;

                if ($this->_isDisplaySupported && $isAutoDisplay) {
                    $data['response']['card'] = [
                        'type' => 'Simple',
                        'title' => isset($this->_serviceAmazonConfig['invocation']) ? ucwords($this->_serviceAmazonConfig['invocation']) : '',
                        'content' => $this->getText()
                    ];
                }
            }

            $data['response']['directives'] = [];
            $data['response']['directives'][] = [
                'type' => 'VideoApp.Launch',
                'videoItem' => [
                    'source' => $this->_videoUrl
                ]
            ];

            if (!empty($this->_videoTitle)) {
                $data['response']['directives'][0]['videoItem']['metadata']['title'] = $this->_videoTitle;
            }

            if (!empty($this->_videoSubtitle)) {
                $data['response']['directives'][0]['videoItem']['metadata']['subtitle'] = $this->_videoSubtitle;
            }

        }

        return $data;
    }

	private function _prepareAplResponse() {
		$this->_logger->info("Printing APL definition in AmazonCommandResponse [" . json_encode($this->_aplDefinition, JSON_PRETTY_PRINT) . "]" );

		$data = array(
			'version' => '1.0',
			'response' => array(),
		);

		$data['response']['shouldEndSession'] = $this->shouldEndSession();

		$data['response']['outputSpeech']['type'] = 'SSML';
		$data['response']['outputSpeech']['ssml'] = $this->getTextSsml();
		$data['response']['directives'] = [];

		if (!empty($this->_aplDefinition)) {
			$data['response']['directives'][] = $this->_prepareAplRenderDocumentDirective();
		}

		if (!empty($this->_aplCommands)) {
			$data['response']['directives'][] = $this->_prepareAplExecuteCommandsDirective();
		}

		$this->_logger->info("Printing APL response in AmazonCommandResponse [" . json_encode($data, JSON_PRETTY_PRINT) . "]" );

    	return $data;
	}

    private function _prepareDialogDelegateResponse() {
        $data = array(
            'version' => '1.0',
            'response' => array(),
        );

        $data['response']['shouldEndSession'] = $this->shouldEndSession();
        $data['response']['directives'] = [];

        if ($this->_shouldDelegateToAlexaDialog) {
            $data['response']['directives'][] = $this->_prepareDelegateDirective();
        }

        $this->_logger->info("Printing Dialog Delegate response in AmazonCommandResponse [" . json_encode($data, JSON_PRETTY_PRINT) . "]" );

        return $data;
    }

	private function _prepareSalesDirectiveResponse() {
		$data = array(
			'version' => '1.0',
			'response' => array(),
		);

		$data['response']['shouldEndSession'] = true;

		$data['response']['directives'] = [];

		if (!empty($this->_salesDirective)) {
			$data['response']['directives'][] = $this->_salesDirective;
		}

		$this->_logger->info("Printing Sales Directive Request in AmazonCommandResponse [" . json_encode($data, JSON_PRETTY_PRINT) . "]" );

		return $data;
	}

    private function _prepareVoicePinConfirmationDirectiveResponse() {
        $data = array(
            'version' => '1.0',
            'response' => array(),
        );

        if (!empty($this->getText())) {
            $data['response']['outputSpeech']['type'] = 'SSML';
            $data['response']['outputSpeech']['ssml'] = $this->getTextSsml();
        }

        $voicePinConfirmationDirective = [
            'type' => 'Connections.StartConnection',
            'uri' => 'connection://AMAZON.VerifyPerson/2',
            'input' => [
                'requestedAuthenticationConfidenceLevel' => [
                    'level' => 400,
                    'customPolicy' => [
                        'policyName' => 'VOICE_PIN'
                    ]
                ]
            ]
        ];

        if (!empty($this->_voicePinConfirmationDirectiveToken)) {
            $voicePinConfirmationDirective['token'] = $this->_voicePinConfirmationDirectiveToken;
        }

        $data['response']['directives'] = [];
        $data['response']['directives'][] = $voicePinConfirmationDirective;

        $this->_logger->info("Printing Voice PIN Confirmation Directive Response in AmazonCommandResponse [" . json_encode($data, JSON_PRETTY_PRINT) . "]" );

        return $data;
    }

	private function _prepareAplRenderDocumentDirective() {
    	return [
			'type' => 'Alexa.Presentation.APL.RenderDocument',
			'token' => $this->_aplToken,
			'document' => $this->_aplDefinition['document'],
			'datasources' => $this->_aplDefinition['datasources'],
			'sources' => !empty($this->_aplDefinition['sources']) ? $this->_aplDefinition['sources'] : (object)$this->_aplDefinition['sources'],
		];
	}

	private function _prepareAplExecuteCommandsDirective() {
		return [
			'type' => 'Alexa.Presentation.APL.ExecuteCommands',
			'token' => empty($this->_aplCommandToken) ? $this->_amazonCommandRequest->getAplToken() : $this->_aplCommandToken,
			'commands' => $this->_aplCommands
		];
	}

    private function _prepareDelegateDirective() {
        $directive['type'] = 'Dialog.Delegate';

        if (!empty($this->_dialogDelegateDirectiveUpdatedIntent)) {
            $directive['updatedIntent'] = $this->_dialogDelegateDirectiveUpdatedIntent;
        }

        return $directive;
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

            $isAutoDisplay = isset($this->_serviceAmazonConfig['auto_display']) ? $this->_serviceAmazonConfig['auto_display'] : false;

            if ($this->_amazonCommandRequest->isAplEnabled()) {
                $isAutoDisplay = false;
            }

            if (($isAutoDisplay) || ($this->_shouldSendSimpleCard)) {
                $title = isset($this->_serviceAmazonConfig['invocation']) ? ucwords($this->_serviceAmazonConfig['invocation']) : '';
                $content = $this->getText();

                if ($this->_shouldSendSimpleCard) {
                    $title = $this->_simpleCardTitle;
                    $content = $this->_simpleCardContent;
                }

                $data['response']['card'] = [
                    'type' => 'Simple',
                    'title' => $title,
                    'content' => $content
                ];
            }

            if ($this->_shouldSendStandardCard) {
                $data['response']['card'] = [
                    'type' => 'Standard',
                    'title' => $this->_standardCardTitle,
                    'text' => $this->_standardCardText
                ];

                if (!empty($this->_standardCardSmallImageURL) && !empty($this->_standardCardLargeImageURL)) {
                    $data['response']['card']['image'] = [
                        'smallImageUrl' => $this->_standardCardSmallImageURL,
                        'largeImageUrl' => $this->_standardCardLargeImageURL
                    ];
                }
            }

            if ($this->_sendAccountLinkingCard) {
                $data['response']['card'] = ['type' => 'LinkAccount'];
            }

			if ($this->_sendPermissionsConsentCard) {
				$data['response']['card'] = [
					'type' => 'AskForPermissionsConsent',
					'permissions' => $this->_permissionsToAskFor
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

    public function startVideoPlayback($url, $title, $subTitle) {
        $this->_videoUrl = $url;
        $this->_videoTitle =$title;
        $this->_videoSubtitle = $subTitle;
        $this->prepareResponse(IAlexaResponseType::VIDEO_RESPONSE);
        $this->getPlatformResponse();
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

    public function playSong(IAudioFile $song, $offset = 0)
    {
        $songDataToTokenize = [
            'audio_url' => $song->getFileUrl(),
            'initiated_by' => IMediaType::MEDIA_TYPE_AUDIO_STREAM,
            'service_id' => $this->_amazonCommandRequest->getServiceId()
        ];

        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMetadata( [
            'artist' => $song->getArtist(),
            'song' => $song->getSongTitle(),
            'art' => $song->getSongImageUrl(),
            'backgroundImage' => $song->getSongBackgroundUrl(),
        ]);
        $this->setOffsetMilliseconds($offset);
        $this->setUrl($song->getFileUrl());
        $this->setCurrentSongToken($this->_generateAudioItemToken($songDataToTokenize));
        $this->setMode("play");

        $this->getPlatformResponse();
    }

    public function enqueueSong(IAudioFile $playingSong, IAudioFile $enqueuingSong)
    {
        $previousSongDataToTokenize = [
            'audio_url' => $playingSong->getFileUrl(),
            'initiated_by' => IMediaType::MEDIA_TYPE_AUDIO_STREAM,
            'service_id' => $this->_amazonCommandRequest->getServiceId()
        ];
        $nextSongDataToTokenize = [
            'audio_url' => $enqueuingSong->getFileUrl(),
            'initiated_by' => IMediaType::MEDIA_TYPE_AUDIO_STREAM,
            'service_id' => $this->_amazonCommandRequest->getServiceId()
        ];

        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setOffsetMilliseconds(0);
        $this->setUrl($enqueuingSong->getFileUrl());
        $this->setMetadata( [
            'artist' => $enqueuingSong->getArtist(),
            'song' => $enqueuingSong->getSongTitle(),
            'art' => $enqueuingSong->getSongImageUrl(),
            'backgroundImage' => $enqueuingSong->getSongBackgroundUrl(),
        ]);
        $this->setPreviousSongToken($this->_generateAudioItemToken($previousSongDataToTokenize));
        $this->setCurrentSongToken($this->_generateAudioItemToken($nextSongDataToTokenize));

        $this->setMode("enqueue");

        $this->getPlatformResponse();
    }

    /**
     * @deprecated
     */
    public function resumeSong(IAudioFile $song, $offset) : array
    {
        return $this->playSong($song, $offset);
    }

    public function stopSong()
    {
        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMode("stop");
        $this->getPlatformResponse();
    }

    public function emptyResponse()
    {
        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMode("other");
        $this->getPlatformResponse();
    }

    public function clearQueue()
    {
        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMode("clearEnqueue");
        $this->getPlatformResponse();
    }

    public function startRadioStream(IRadioStream $radioStream)
    {
        $dataToTokenize = [
            'radio_stream' => [
                'radio_station_name' => $radioStream->getRadioStationName(),
                'radio_station_slogan' => $radioStream->getRadioStationSlogan(),
                'radio_station_logo_url' => $radioStream->getRadioStationLogoUrl(),
                'stream_url' => $radioStream->getRadioStreamUrl()
            ],
            'initiated_by' => IMediaType::MEDIA_TYPE_RADIO_STREAM,
            'service_id' => $this->_amazonCommandRequest->getServiceId(),
            'timestamp' => time()
        ];

        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMetadata( [
            'artist' => $radioStream->getRadioStationSlogan(),
            'song' => $radioStream->getRadioStationName(),
            'art' => $radioStream->getRadioStationLogoUrl()
        ]);
        $this->setOffsetMilliseconds(0);
        $this->setUrl($radioStream->getRadioStreamUrl());
        $this->setCurrentSongToken($this->_generateAudioItemToken($dataToTokenize));
        $this->setMode("play");
    }

    public function stopRadioStream()
    {
        $this->prepareResponse(IAlexaResponseType::MEDIA_RESPONSE);
        $this->setMode("stop");
        $this->getPlatformResponse();
    }

    private function _generateAudioItemToken($array) {
        $token = serialize($array);
        return base64_encode($token);
    }
}
