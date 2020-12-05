<?php


namespace Convo\Core\Adapters\Google\Common\Intent;


use Convo\Core\Adapters\Google\Common\IResponseType;

class GoogleActionsSystemIntent
{
    public function prepareSystemIntent($listProperties) {
        $systemIntent = array(
            "intent" => IActionsIntent::OPTION,
            "data" => array(
                "@type" => IIntentInputValueDataSpec::OPTION_VALUE_SPEC,
            )
        );

        $selectionItems = $this->_prepareItemSelection($listProperties);

        switch ($listProperties["list_template"]) {
            case IResponseType::LIST:
                $systemIntent["data"]["listSelect"]["items"] = $selectionItems;
                if (!empty($listProperties["list_title"])) {
                    $systemIntent["data"]["listSelect"]["title"] = $listProperties["list_title"];
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

    private function _prepareItemSelection($listProperties) {
        $listItems = array();
        foreach ($listProperties["list_items"] as $listItem) {
            $obj = array(
                "optionInfo" => array(
                    "key" => $listItem["list_item_key"],
                    "synonyms" => [$listItem["list_item_title"]]
                ),
                "title" => $listItem["list_item_title"],
            );


            if (!empty($listItem["list_item_description_1"])) {
                $obj["description"] = $listItem["list_item_description_1"];
            }

            if (!empty($listItem["list_item_image_url"]) && !empty($listItem["list_item_image_text"])) {
                $obj["image"] = array(
                    "url" => $listItem["list_item_image_url"],
                    "accessibilityText" => $listItem["list_item_image_text"]
                );
            }

            array_push($listItems, $obj);
        }

        return $listItems;
    }

    public function __toString()
    {
        return get_class($this);
    }
}
