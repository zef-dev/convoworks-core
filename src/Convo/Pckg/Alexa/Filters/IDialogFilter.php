<?php

namespace Convo\Pckg\Alexa\Filters;

interface IDialogFilter
{
    public function getAlexaPrompts();

    public function getDialogValidators();
}
