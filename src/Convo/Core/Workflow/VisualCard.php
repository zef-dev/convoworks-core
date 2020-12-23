<?php


namespace Convo\Core\Workflow;


class VisualCard implements IVisualCard
{
    private $_cardDefinition;
    private $_cardActions;

    /**
     * VisualCard constructor.
     * @param $cardDefinition
     * @param $cardActions
     */
    public function __construct($cardDefinition, $cardActions = [])
    {
        $this->_cardDefinition = $cardDefinition;
        $this->_cardActions = $cardActions;
    }


    public function getCardVisualItem(): IVisualItem
    {
        return $this->_cardDefinition;
    }

    public function getCardActions(): array
    {
        return $this->_cardActions;
    }
}
