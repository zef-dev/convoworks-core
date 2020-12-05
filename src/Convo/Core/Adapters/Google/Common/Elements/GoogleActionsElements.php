<?php


namespace Convo\Core\Adapters\Google\Common\Elements;

use Convo\Core\Adapters\Google\Common\Intent\IActionsIntent;

class GoogleActionsElements
{
    /**
     * Generates the simple response element for Google Assistant App.
     *
     * @param $name
     * @param $ssml
     * @param $displayText
     * @param string $intent
     * @return array
     */
    public function getSimpleResponseElement($name, $ssml, $displayText, $intent = '') {
        $simpleResponse = array(
            "name" => $name,
            "simpleResponse" => array(
                "ssml" => $ssml,
                "displayText" => $displayText
            )
        );

        if ($intent === IActionsIntent::CONFIRMATION) {
            $simpleResponse = array(
                "name" => "Confirmation Response",
                "simpleResponse" => array(
                    "ssml" => $ssml,
                    "displayText" => $displayText
                )
            );
        }

        return $simpleResponse;
    }

    public function getBasicCardResponseElement($value) {
        $basicCardBody = array(
            "formattedText" => $value["data_item_description_1"]
        );

        if (!empty($value["data_item_title"])) {
            $basicCardBody["title"] = $value["data_item_title"];
        }

        if (!empty($value["data_item_subtitle"])) {
            $basicCardBody["subtitle"] = $value["data_item_subtitle"];
        }

        if (!empty($value["data_item_image_url"]) && !empty($value["data_item_image_text"])) {
            $basicCardBody["image"] = array (
                "url" => $value["data_item_image_url"],
                "accessibilityText" => $value["data_item_image_text"],
            );
        }

        return array("basicCard" => $basicCardBody);
    }

    public function getCarouselBrowseResponseElement($value) {
        $browseCarouselItems = array();
        foreach ($value["browse_carousel_items"] as $browseCarouselItem) {
            $obj = array(
                "title" => $browseCarouselItem["browse_carousel_item_title"],
            );

            if (!empty($browseCarouselItem["browse_carousel_item_description"])) {
                $obj["description"] = $browseCarouselItem["browse_carousel_item_description"];
            }

            if (!empty($browseCarouselItem["browse_carousel_item_footer"])) {
                $obj["footer"] = $browseCarouselItem["browse_carousel_item_footer"];
            }

            if (!empty($browseCarouselItem["browse_carousel_item_image_url"]) && !empty($browseCarouselItem["browse_carousel_item_image_text"])) {
                $obj["image"] = array(
                    "url" => $browseCarouselItem["browse_carousel_item_image_url"],
                    "accessibilityText" => $browseCarouselItem["browse_carousel_item_image_text"]
                );
            }

            if (!empty($browseCarouselItem["browse_carousel_item_url"])) {
                $obj["openUrlAction"] = array(
                    "url" => $browseCarouselItem["browse_carousel_item_url"],
                );
            }

            array_push($browseCarouselItems, $obj);
        }
        return array("carouselBrowse" => array("items" => $browseCarouselItems));
    }

    public function getTextToSpeechResponseElement($text) {
        return array(
            "simpleResponse" => array(
                "textToSpeech" => "<speak>$text</speak>"
            )
        );
    }

    /**
     *  Generates the suggestions chips for Google Assistant App.
     *
     * @param array $suggestions
     * @return array
     */
    public function getSuggestionsElement($suggestions) {
        $suggestionObjects = [];
        foreach ($suggestions as $suggestion) {
            $suggestionObject = array(
                "title" => $suggestion
            );
            array_push($suggestionObjects, $suggestionObject);
        }

        return $suggestionObjects;
    }

    /**
     * Generates the media response element for Google Assistant App
     * @param $mp3Url
     * @param $fileNameFromMp3Url
     * @return array
     */
    public function getMediaResponseElement($mp3Url, $fileNameFromMp3Url) {
        return array(
            "name" => "Music Response with Media",
            "mediaResponse" => array(
                "mediaType" => "AUDIO",
                "mediaObjects" => array(
                    array(
                        "contentUrl" => $mp3Url,
                        "name" => $fileNameFromMp3Url,
                        "description" => $mp3Url,
                        "largeImage" => array(
                            "url" => "https://zef.dev/images/zefdev-800x650.png",
                            "accessibilityText" => "Zef Dev",
                        )
                    )
                )
            )
        );
    }

    public function __toString()
    {
        return get_class($this);
    }
}
