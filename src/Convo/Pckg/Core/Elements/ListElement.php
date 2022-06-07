<?php declare(strict_types=1);
namespace Convo\Pckg\Core\Elements;

use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

/**
 * Class ListElement
 * @package Convo\Pckg\Core\Elements
 * @deprecated
 */

class ListElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_listTitle;
    /** @var array */
	private $_dataCollection = array();

	private $_offset;
	private $_limit;

	private $_listTemplate;

	private $_listItemTitle;
	private $_listItemDescription1;
	private $_listItemDescription2;
	private $_listItemImageUrl;
	private $_listItemImageText;

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_listTitle = $properties['list_title'];
        $this->_dataCollection = $properties['data_collection'];

        $this->_listTemplate = $properties['list_template'];

        $this->_offset = $properties['offset'];
        $this->_limit = $properties['limit'];

        $this->_listItemTitle = $properties['list_item_title'];
        $this->_listItemDescription1 = $properties['list_item_description_1'];
        $this->_listItemDescription2 = $properties['list_item_description_2'];
        $this->_listItemImageUrl = $properties['list_item_image_url'];
        $this->_listItemImageText = $properties['list_item_image_text'];
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $listTitle = $this->evaluateString($this->_listTitle);
        $listTemplate = $this->evaluateString($this->_listTemplate);
        $items = $this->evaluateString($this->_dataCollection);
        $limit = $this->evaluateString($this->_limit);

        $slot_name = $this->evaluateString('listItem');

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

        if ($limit !== null) {
            $limit = abs($limit);
            $end = min(($start + $limit), count($items));
        }

        $listItems = array();
        for ($i = $start; $i < $end; ++$i) {
            $val = $items[$i];
            $params->setServiceParam($slot_name, [
                'value' => $val,
                'index' => $i,
                'natural' => $i + 1,
                'first' => $i === $start,
                'last' => $i === $end
            ]);
            array_push($listItems,
                array(
                    "list_item_key" => $this->evaluateString(strval($i)),
                    "list_item_title" => $this->evaluateString($this->_listItemTitle),
                    "list_item_description_1" => $this->evaluateString($this->_listItemDescription1),
                    "list_item_description_2" => $this->evaluateString($this->_listItemDescription2),
                    "list_item_image_url" => $this->evaluateString($this->_listItemImageUrl),
                    "list_item_image_text" => $this->evaluateString($this->_listItemImageText),
                )
            );
        }

        $data = array(
            "list_title" => $listTitle,
            "list_template" => $listTemplate,
            "list_items" => $listItems,
        );

        $this->_logger->debug('List element read method executed ['.print_r( $data, true).']');

        if (is_a( $response, 'Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse'))
        {
            $this->_logger->debug('Google action invoked with dialogflow ['.$response->getText().']');
            /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse  $response */
            $response->prepareResponse(IResponseType::LIST, $data);
        }

        // todo add handling for gactions and alexa
        if (is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
        {

            $this->_logger->debug('Amazon command invoked ['.$response->getText().']');

            $response->setDataList( $data);

            if ($request->getIsDisplaySupported()  && $request->getIsAplSupported())
            {
                /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response*/
                $response->prepareResponse(IAlexaResponseType::LIST_RESPONSE);
            }
            else
            {
                $this->_logger->debug('Display is not supported on this device.');
            }
        }
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
