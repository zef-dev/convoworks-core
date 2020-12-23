<?php


namespace Convo\Core\Workflow;


class VisualList implements IVisualList
{
    private $_listTitle;
    private $_listType;
    private $_listItems;

    /**
     * VisualList constructor.
     * @param $title
     * @param $listType
     * @param $listItems
     */
    public function __construct($title, $listType, $listItems)
    {
        $this->_listTitle = $title;
        $this->_listType = $listType;
        $this->_listItems = $listItems;
    }

    /**
     * @return string
     */
    public function getListTitle(): string
    {
        return $this->_listTitle;
    }

    public function getListType(): string
    {
        return $this->_listType;
    }

    /**
     * @return array
     */
    public function getListItems(): array
    {
        return $this->_listItems;
    }
}
