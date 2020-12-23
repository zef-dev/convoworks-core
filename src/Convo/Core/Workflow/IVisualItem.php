<?php declare(strict_types=1);


namespace Convo\Core\Workflow;


interface IVisualItem
{
    public function getTitle() : string;
    public function getSubtitle() : string;
    public function getDescription() : string;
    public function getImageURL() : string;
    public function getImageText() : string;
}
