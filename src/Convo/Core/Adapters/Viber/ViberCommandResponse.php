<?php


namespace Convo\Core\Adapters\Viber;


use Convo\Core\Adapters\Fbm\FacebookMessengerCommandRequest;
use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Workflow\ICardAction;
use Convo\Core\Workflow\IConvoCardResponse;
use Convo\Core\Workflow\IConvoListResponse;
use Convo\Core\Workflow\IVisualCard;
use Convo\Core\Workflow\IVisualItem;
use Convo\Core\Workflow\IVisualList;

class ViberCommandResponse extends \Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse implements IConvoListResponse, IConvoCardResponse
{
    private $_texts =	[];
    private $_text = "";
    private $_receiver = "";
    private $_senderName = "";
    private $_responseType = IResponseType::SIMPLE_RESPONSE;
    private $_value;

    /**
     * @var ViberCommandRequest
     */
    private $_viberCommandRequest;

    /**
     * FacebookMessengerCommandResponse constructor.
     * @param ViberCommandRequest $viberCommandRequest
     */
    public function __construct(ViberCommandRequest $viberCommandRequest)
    {
        parent::__construct();
        $this->_viberCommandRequest = $viberCommandRequest;
    }

    public function addText($text, $append = false)
    {
        parent::addText($text, $append);
        $resultText = preg_replace('/\s\s+/', ' ', strip_tags($text));
        $this->_texts[]	= $resultText;
    }

    public function setResponseType($responseType) {
        $this->_responseType = $responseType;
    }

    public function setValue($value) {
        $this->_value = $value;
    }

    public function setText($text) {
        $this->_text = $text;
    }

    public function getTexts() {
        return $this->_texts;
    }

    public function setSenderName($serviceId) {
        $this->_senderName = $this->_serviceIdToName($serviceId);
    }

    public function setReceiver($sessionId) {
        $this->_receiver = $sessionId;
    }

    public function getPlatformResponse()
    {
        $response = [
            "receiver" => $this->_receiver,
            "sender" => [
                "name" => $this->_senderName
            ]
        ];
        switch ($this->_responseType) {
            case IResponseType::LIST:
                $response['type'] = 'text';
                $response['text'] = implode("\n\n", array_map( function ( $item) { return $item; }, $this->getTexts()));
                $response['keyboard'] = $this->_prepareKeyboardAsList($this->_value);
                break;
            case IResponseType::BASIC_CARD:
                $response['type'] = 'rich_media';
                /*
                "rich_media":{
                    "Type":"rich_media",
                    "BgColor":"#FFFFFF",
                    "Buttons":[
                        {
                            "Columns":6,
                            "Rows":1,
                            "ActionType":"reply",
                            "ActionBody":'card_action_more_details',
                            "Text":"MORE DETAILS",
                            "TextSize":"small",
                            "TextVAlign":"middle",
                            "TextHAlign":"middle"
                        },
                        {
                            "Columns":6,
                            "Rows":1,
                            "ActionType":"reply",
                            "ActionBody":'card_action_more_details_2',
                            "Text":"MORE DETAILS 2",
                            "TextSize":"small",
                            "TextVAlign":"middle",
                            "TextHAlign":"middle"
                        }
                    ]
                }
                */

                /**
                 * @var $cardDefinition IVisualCard
                 */
                $cardDefinition = $this->_value;

                $imageURL = $cardDefinition->getCardVisualItem()->getImageURL();
                $title = $cardDefinition->getCardVisualItem()->getTitle();
                $subtitle = $cardDefinition->getCardVisualItem()->getSubtitle();
                $description = $cardDefinition->getCardVisualItem()->getDescription();

                $response['rich_media'] = [
                    "Type" => "rich_media",
                    "BgColor" => "#FFFFFF",
                    "Buttons" => [
                        [
                            "Columns" => 6,
                            "Rows" => 3,
                            "ActionType" => "reply",
                            "ActionBody" => 'no_action',
                            "Image" => $imageURL
                        ],
                        [
                            "Columns" => 6,
                            "Rows" => 1,
                            "Text" => "<b>$title</b><br>$subtitle<br>$description",
                            "ActionType" => "reply",
                            "ActionBody" => 'non_action',
                            "TextSize" => "medium",
                            "TextVAlign" => "middle",
                            "TextHAlign" => "left"
                        ]
                    ]
                ];
                $cardActions = array_slice($cardDefinition->getCardActions(), 0, 3);
                $numberOfRows = 1;
                if (count($cardActions) === 1) {
                    $numberOfRows = 3;
                }
                foreach ($cardActions as $cardAction) {
                    /**
                     * @var $cardAction ICardAction
                     */
                    array_push($response['rich_media']['Buttons'], [
                            "Columns" => 6,
                            "Rows" => $numberOfRows,
                            "ActionType" => "reply",
                            "ActionBody" => $cardAction->getCardActionKey(),
                            "Text" => $cardAction->getCardActionName(),
                            "TextSize" => "small",
                            "TextVAlign" => "middle",
                            "TextHAlign" => "middle"
                    ]);
                }
                break;
            default:
                $response['type'] = 'text';
                $response['text'] = implode("\n\n", array_map( function ( $item) { return $item; }, $this->getTexts()));
                break;
        }

        return $response;
    }

    private function _serviceIdToName($serviceId)
    {
        $str = str_replace("-", " ", $serviceId);
        $str = ucwords($str);
        return substr($str, "0", "28");
    }

    private function _prepareKeyboardAsList(IVisualList $listDefinition) {
        $buttons = [];
        $listItemIndex = 0;
        foreach ($listDefinition->getListItems() as $listItem) {
            /**
             * @var $listItem IVisualItem
             */
            $listItemTitle = $listItem->getTitle();
            $listItemSubTitle = $listItem->getSubtitle();

            if (!empty($listItem->getImageURL())) {
                $listButtonImageDefinition = [
                    "Columns" => 2,
                    "Rows" => 2,
                    "ActionType" => "reply",
                    "ActionBody" => "list_item_" . strval($listItemIndex),
                    "BgColor" => "#f6f7f9",
                    "Image" => $listItem->getImageURL()
                ];

                $listButtonTextDefinition = [
                    "Columns" => 4,
                    "Rows" => 2,
                    "ActionType" => "reply",
                    "ActionBody" => "list_item_" . strval($listItemIndex),
                    "Text" => "<br><b>$listItemTitle</b><br>$listItemSubTitle",
                    "TextSize" => "regular",
                    "TextHAlign" => "left",
                    "TextVAlign" => "top",
                    "BgColor" => "#f6f7f9"
                ];
                array_push($buttons, $listButtonImageDefinition, $listButtonTextDefinition);
            } else {
                $listButtonTextDefinition = [
                    "Columns" => 6,
                    "Rows" => 2,
                    "ActionType" => "reply",
                    "ActionBody" => "list_item_" . strval($listItemIndex),
                    "Text" => "<br><b>$listItemTitle</b><br>$listItemSubTitle",
                    "TextSize" => "regular",
                    "TextHAlign" => "left",
                    "TextVAlign" => "top",
                    "BgColor" => "#f6f7f9"
                ];
                array_push($buttons, $listButtonTextDefinition);
            }

            $listItemIndex++;
        }
        return [
            "Type" => "keyboard",
            "Buttons" => $buttons
        ];
    }

    public function getCardResponse(IVisualCard $cardItem): array
    {
        $this->setResponseType(IResponseType::BASIC_CARD);
        $this->setValue($cardItem);
        return $this->getPlatformResponse();
    }

    public function getListResponse(IVisualList $listDefinition): array
    {
        $this->setResponseType(IResponseType::LIST);
        $this->setValue($listDefinition);
        return $this->getPlatformResponse();
    }
}
