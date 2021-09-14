<?php


namespace Convo\Pckg\Dialogflow\Elements;


use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class BrowseCarouselElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    /** @var array */
    private $_dataCollection = array();

    private $_offset;
    private $_limit;

    private $_browseCarouselItemTitle;
    private $_browseCarouselItemDescription;
    private $_browseCarouselItemFooter;
    private $_browseCarouselItemImageUrl;
    private $_browseCarouselItemImageText;
    private $_browseCarouselItemUrl;

    public function __construct($properties)
    {
        parent::__construct($properties);
        $this->_dataCollection = $properties['data_collection'];

        $this->_offset = $properties['offset'];
        $this->_limit = $properties['limit'];

        $this->_browseCarouselItemTitle = $properties['browse_carousel_item_description'];
        $this->_browseCarouselItemDescription = $properties['browse_carousel_item_description'];
        $this->_browseCarouselItemFooter = $properties['browse_carousel_item_footer'];
        $this->_browseCarouselItemImageUrl = $properties['browse_carousel_item_image_url'];
        $this->_browseCarouselItemImageText = $properties['browse_carousel_item_image_text'];
        $this->_browseCarouselItemUrl = $properties['browse_carousel_item_url'];
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $items = $this->evaluateString($this->_dataCollection);

        $slot_name = $this->evaluateString('browseCarouselItem');

        $scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST;
        $params = $this->getService()->getComponentParams( $scope_type, $this);

        $start = 0;
        $end = count($items) - 1;

        if ($this->_offset !== null) {
            if ($this->_offset > $end || $this->_offset < 0) {
                $this->_logger->warning('Offset ['.$this->_offset.'] falls outside the range ['.$start.', '.$end.']. Starting from 0.');
            } else {
                $start = $this->_offset;
            }
        }

        if ($this->_limit !== null) {
            $limit = abs($this->_limit);
            $end = min(($start + $limit), count($items));
        }

        $browseCarouselItems = array();
        for ($i = $start; $i < $end; ++$i) {
            $val = $items[$i];
            $params->setServiceParam($slot_name, [
                'value' => $val,
                'index' => $i,
                'natural' => $i + 1,
                'first' => $i === $start,
                'last' => $i === $end
            ]);
            array_push($browseCarouselItems,
                array(
                    "browse_carousel_item_key" => $this->evaluateString(strval($i)),
                    "browse_carousel_item_title" => $this->evaluateString($this->_browseCarouselItemTitle),
                    "browse_carousel_item_description" => $this->evaluateString($this->_browseCarouselItemDescription),
                    "browse_carousel_item_footer" => $this->evaluateString($this->_browseCarouselItemFooter),
                    "browse_carousel_item_image_url" => $this->evaluateString($this->_browseCarouselItemImageUrl),
                    "browse_carousel_item_image_text" => $this->evaluateString($this->_browseCarouselItemImageText),
                    "browse_carousel_item_url" => $this->evaluateString($this->_browseCarouselItemUrl),
                )
            );
        }

        $data = array(
            "browse_carousel_items" => $browseCarouselItems,
        );

        $this->_logger->debug('List element read method executed ['.print_r( $data, true).']');

        if (is_a( $response, 'Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse'))
        {
            $this->_logger->debug('Google action invoked with dialogflow ['.$response->getText().']');
            /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse  $response */
            $response->prepareResponse(IResponseType::CAROUSEL_BROWSE, $data);
        }
    }

    public function evaluateString( $string, $context=[]) {
        $own_params	= $this->getService()->getAllComponentParams( $this);
        return parent::evaluateString( $string, array_merge( $own_params, $context));
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
