<?php


namespace Convo\Core\Adapters\Google\Common\Intent;


interface IIntentInputValueDataSpec
{
    public const OPTION_VALUE_SPEC = 'type.googleapis.com/google.actions.v2.OptionValueSpec';
    public const CONFIRMATION_VALUE_SPEC = 'type.googleapis.com/google.actions.v2.ConfirmationValueSpec';
    public const DATE_TIME_VALUE_SPEC = 'type.googleapis.com/google.actions.v2.DateTimeValueSpec';
    public const PLACE_VALUE_SPEC = 'type.googleapis.com/google.actions.v2.PlaceValueSpec';
    public const SIGN_IN_VALUE_SPEC = 'type.googleapis.com/google.actions.v2.SignInValueSpec';
}
