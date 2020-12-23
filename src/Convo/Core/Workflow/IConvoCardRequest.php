<?php


namespace Convo\Core\Workflow;



interface IConvoCardRequest extends IConvoRequest
{
    /**
     * @return string
     */
    public function getSelectedCardAction() : string;
}
