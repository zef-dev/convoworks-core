<?php

namespace Convo\Pckg\Alexa\Filters;

interface IAlexaDialogIntentSlotFilter
{
    public function getDialogValidators();

    public function getAlexaPrompts();

    public function getTargetSlot();

    public function getIntentSlotConfirmationAlexaPrompts();

    public function getUserUtterances();
}
