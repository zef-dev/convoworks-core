<?php


namespace Convo\Core\Workflow;



interface IConvoAudioRequest extends IIntentAwareRequest
{
    public function getOffset();
}
