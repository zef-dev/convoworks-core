<?php


namespace Convo\Core\Workflow;



interface IConvoCardResponse extends IConvoResponse
{
    public function getCardResponse(IVisualCard $cardItem) : array;
}
