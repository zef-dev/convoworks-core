<?php

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Intent\IntentModel;

interface IAlexaDialogDriven
{
    public function getDialogDefinition();
}
