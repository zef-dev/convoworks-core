<?php


namespace Convo\Core\Workflow;



interface IConvoListResponse extends IConvoResponse
{
    public function getListResponse(IVisualList $listDefinition) : array;
}
