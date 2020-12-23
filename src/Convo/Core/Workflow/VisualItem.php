<?php


namespace Convo\Core\Workflow;


class VisualItem implements IVisualItem
{
    private $_title;
    private $_subTitle;
    private $_description;
    private $_imageURL;
    private $_imageText;

    /**
     * VisualItem constructor.
     * @param $title
     * @param $subTitle
     * @param $description
     * @param $imageURL
     * @param $imageText
     */
    public function __construct($title, $subTitle, $description, $imageURL, $imageText)
    {
        $this->_title = $title;
        $this->_subTitle = $subTitle;
        $this->_description = $description;
        $this->_imageURL = $imageURL;
        $this->_imageText = $imageText;
    }

    public function getTitle() : string
    {
        return $this->_title;
    }

    public function getSubtitle() : string
    {
        return $this->_subTitle;
    }

    public function getDescription() : string
    {
        return $this->_description;
    }

    public function getImageURL() : string
    {
        return $this->_imageURL;
    }

    public function getImageText() : string
    {
        return $this->_imageText;
    }
}
