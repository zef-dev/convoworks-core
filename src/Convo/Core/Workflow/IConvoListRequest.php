<?php


namespace Convo\Core\Workflow;



interface IConvoListRequest extends IConvoRequest
{
    /**
     * @return int
     */
    public function getSelectedItemIndex() : int;
}
