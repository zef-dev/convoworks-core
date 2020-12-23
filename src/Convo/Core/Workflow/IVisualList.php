<?php


namespace Convo\Core\Workflow;


interface IVisualList {
    public function getListTitle() : string;
    public function getListType() : string;
    public function getListItems() : array;
}
