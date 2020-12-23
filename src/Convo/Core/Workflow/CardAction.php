<?php


namespace Convo\Core\Workflow;


class CardAction implements ICardAction
{
    private $_cardActionKey;
    private $_cardActionName;

    /**
     * CardAction constructor.
     * @param $cardActionKey
     * @param $cardActionName
     */
    public function __construct($cardActionKey, $cardActionName)
    {
        $this->_cardActionKey = $cardActionKey;
        $this->_cardActionName = $cardActionName;
    }


    public function getCardActionKey(): string
    {
        return $this->_cardActionKey;
    }

    public function getCardActionName(): string
    {
        return $this->_cardActionName;
    }
}
