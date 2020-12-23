<?php


namespace Convo\Core\Workflow;


interface IVisualCard {
    public function getCardVisualItem() : IVisualItem;
    public function getCardActions() : array;
}
