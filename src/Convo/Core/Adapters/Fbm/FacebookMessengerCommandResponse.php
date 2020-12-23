<?php


namespace Convo\Core\Adapters\Fbm;


use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Workflow\ICardAction;
use Convo\Core\Workflow\IConvoCardResponse;
use Convo\Core\Workflow\IConvoListResponse;
use Convo\Core\Workflow\IVisualCard;
use Convo\Core\Workflow\IVisualItem;
use Convo\Core\Workflow\IVisualList;

class FacebookMessengerCommandResponse extends \Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse implements IConvoListResponse, IConvoCardResponse
{
    private $_texts = [];
    private $_text = "";

    private $_responseType = IResponseType::SIMPLE_RESPONSE;
    private $_value;
    /**
     * @var FacebookMessengerCommandRequest
     */
    private $_facebookMessengerCommandRequest;

    /**
     * FacebookMessengerCommandResponse constructor.
     * @param FacebookMessengerCommandRequest $facebookMessengerCommandRequest
     */
    public function __construct(FacebookMessengerCommandRequest $facebookMessengerCommandRequest)
    {
        parent::__construct();
        $this->_facebookMessengerCommandRequest = $facebookMessengerCommandRequest;
    }


    public function addText($text, $append = false)
    {
        parent::addText($text);
        $resultText = preg_replace('/\s\s+/', ' ', strip_tags($text));
        $this->_texts[] = $resultText;
    }

    public function setText($text)
    {
        $this->_text = strip_tags($text);
    }

    public function getTexts()
    {
        return $this->_texts;
    }

    public function getPlatformResponse()
    {
        switch ($this->_responseType) {
            case IResponseType::LIST:
                return [
                    "attachment" => [
                        "type" => "template",
                        "payload" => [
                            "template_type" => "generic",
                            "elements" => $this->_prepareList($this->_value)
                        ]
                    ]
                ];
            case IResponseType::BASIC_CARD:
                return [
                    "attachment" => [
                        "type" => "template",
                        "payload" => [
                            "template_type" => "generic",
                            "elements" => [
                                $this->_prepareCard($this->_value)
                            ]
                        ]
                    ]
                ];
            default:
                return ["text" => $this->_text];
        }
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

    public function getCardResponse(IVisualCard $cardDefinition): array
    {
        $this->prepareResponse(IResponseType::BASIC_CARD, $cardDefinition);
        return $this->getPlatformResponse();
    }

    public function getListResponse(IVisualList $listDefinition): array
    {
        $this->prepareResponse(IResponseType::LIST, $listDefinition);
        return $this->getPlatformResponse();
    }

    private function _prepareCard(IVisualCard $cardDefinition)
    {
        $obj = [
            "title" => "Card Title",
            "subtitle" => "Card Subtitle"
        ];

        if (!empty($cardDefinition->getCardVisualItem())) {
            if (!empty($cardDefinition->getCardVisualItem()->getTitle())) {
                $obj["title"] = $cardDefinition->getCardVisualItem()->getTitle();
            }

            if (!empty($cardDefinition->getCardVisualItem()->getImageURL())) {
                $obj["image_url"] = $cardDefinition->getCardVisualItem()->getImageURL();
            }

            if (!empty($cardDefinition->getCardVisualItem()->getImageURL())) {
                $obj["subtitle"] = $cardDefinition->getCardVisualItem()->getImageURL();
            }

            if (!empty($cardDefinition->getCardActions())) {
                $buttons = array_map(function ($cardAction) {
                    /**
                     * @var ICardAction $cardAction
                     */
                    return [
                        "type" => "postback",
                        "title" => $cardAction->getCardActionName(),
                        "payload" => $cardAction->getCardActionKey(),
                    ];
                },
                    $cardDefinition->getCardActions()
                );
                $obj["buttons"] = $buttons;
            }
        }

        return $obj;
    }

    private function _prepareList(IVisualList $listDefinition)
    {
        $outputListItems = array();
        $listIndex = 0;
        foreach ($listDefinition->getListItems() as $listItem) {
            /**
             * @var $listItem IVisualItem
             */
            $obj = [
                "title" => "List Item Title " . strval($listIndex),
                "subtitle" => "List Item Subtitle " . strval($listIndex),
                "buttons" => [
                    [
                        "type" => "postback",
                        "title" => "Click",
                        "payload" => "list_item_" . strval($listIndex),
                    ]
                ]
            ];

            if (!empty($listItem->getTitle())) {
                $obj["title"] = $listItem->getTitle();
            }

            if (!empty($listItem->getImageURL())) {
                $obj["image_url"] = $listItem->getImageURL();
            }

            if (!empty($listItem->getSubtitle())) {
                $obj["subtitle"] = $listItem->getSubtitle();
            }

            array_push($outputListItems, $obj);
            $listIndex++;
        }

        return $outputListItems;
    }
}
