<?php


namespace Convo\Core\Adapters\Google\Common\Intent;


use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Workflow\IVisualItem;
use Convo\Core\Workflow\IVisualList;
use Convo\Core\Workflow\VisualItem;

class GoogleActionsSystemIntent
{
    public function prepareSystemIntent(IVisualList $listDefinition) {
        $systemIntent = array(
            "intent" => IActionsIntent::OPTION,
            "data" => array(
                "@type" => IIntentInputValueDataSpec::OPTION_VALUE_SPEC,
            )
        );

        $selectionItems = $this->_prepareItemSelection($listDefinition->getListItems());

        switch ($listDefinition->getListType()) {
            case IResponseType::LIST:
                $systemIntent["data"]["listSelect"]["items"] = $selectionItems;
                if (!empty($listDefinition->getListTitle())) {
                    $systemIntent["data"]["listSelect"]["title"] = $listDefinition->getListTitle();
                }
                break;
            case IResponseType::CAROUSEL:
                $systemIntent["data"]["carouselSelect"]["items"] = $selectionItems;
                break;
            default:
                break;
        }

        return $systemIntent;
    }

    /**
     * // TODO list items
     * @param $listItems array
     * @return array
     */
    private function _prepareItemSelection(array $listItems) {
        $outputListItems = array();
        $listIndex = 0;
        foreach ($listItems as $listItem) {
            /**
             * @var $listItem IVisualItem
             */
            $obj = array(
                "optionInfo" => array(
                    "key" => 'list_item_' . $listIndex,
                    "synonyms" => [$listItem->getTitle()]
                ),
                "title" => $listItem->getTitle(),
            );


            if (!empty($listItem->getDescription())) {
                $obj["description"] = $listItem->getDescription();
            }

            if (!empty($listItem->getImageURL()) && !empty($listItem->getImageText())) {
                $obj["image"] = array(
                    "url" => $listItem->getImageURL(),
                    "accessibilityText" => $listItem->getImageText()
                );
            }

            array_push($outputListItems, $obj);
            $listIndex++;
        }

        return $outputListItems;
    }

    public function __toString()
    {
        return get_class($this);
    }
}
