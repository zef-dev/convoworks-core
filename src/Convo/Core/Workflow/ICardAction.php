<?php


namespace Convo\Core\Workflow;


interface ICardAction
{
    public function getCardActionKey() : string;
    public function getCardActionName() : string;
}
