<?php


namespace Convo\Core\Adapters\Google\Common\Intent;


interface IActionsIntent
{
    // action request intent
    public const MAIN = 'actions.intent.MAIN';
    public const TEXT = 'actions.intent.TEXT';
    // action intent after response
    public const MEDIA_STATUS = 'actions.intent.MEDIA_STATUS';
    // action system intents after response
    public const OPTION = 'actions.intent.OPTION';
    public const CONFIRMATION = 'actions.intent.CONFIRMATION';
    public const DATETIME = 'actions.intent.DATETIME';
    public const DELIVERY_ADDRESS = 'actions.intent.DELIVERY_ADDRESS';
    public const PLACE = 'actions.intent.PLACE';
    public const NO_INPUT = 'actions.intent.NO_INPUT';
    public const SIGN_IN = 'actions.intent.SIGN_IN';
    public const CANCEL = 'actions.intent.CANCEL';
    public const ASSISTANT_CANCEL = 'ASSISTANT_CANCEL';
    public const PERMISSION = 'actions.intent.PERMISSION';
    public const REGISTER_UPDATE = 'actions.intent.REGISTER_UPDATE';
    public const TRANSACTION_DECISION = 'actions.intent.TRANSACTION_DECISION';
}
