<?php declare(strict_types=1);
namespace Convo\Pckg\Core\Elements;

use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\VisualItem;
use Convo\Core\Workflow\VisualList;

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
            $listItem = new VisualItem(
                $this->evaluateString($this->_sanitizeString($this->_listItemTitle)),
                $this->evaluateString($this->_sanitizeString($this->_listItemDescription1)),
                $this->evaluateString($this->_sanitizeString($this->_listItemDescription2)),
                $this->evaluateString($this->_listItemImageUrl),
                $this->evaluateString($this->_sanitizeString($this->_listItemImageText))
            );
            array_push($listItems, $listItem);
        }

        $data = new VisualList($listTitle, $listTemplate, $listItems);

        $this->_logger->debug('List element read method executed ['.print_r( $data, true).']');

        if (is_a( $response, 'Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse'))
        {
            $this->_logger->debug('Google action invoked with dialogflow ['.$response->getText().']');
            /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse  $response */
            $response->prepareResponse(IResponseType::LIST, $data);
        }

        if (is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
        {

            $this->_logger->debug('Amazon command invoked ['.$response->getText().']');

            $response->setDataList( $data);

            if ($request->getIsDisplaySupported() && $request->getIsDisplayInterfaceEnabled())
            {
                /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response*/
                $response->prepareResponse(IAlexaResponseType::LIST_RESPONSE);
            }
            else
            {
                $this->_logger->debug('Display is not supported on this device.');
            }
        }

        if (is_a( $response, 'Convo\Core\Adapters\Fbm\FacebookMessengerCommandResponse'))
        {
            $this->_logger->debug('Facebook Messenger ['.$response->getText().']');
            /* @var \Convo\Core\Adapters\Fbm\FacebookMessengerCommandResponse  $response */
            $response->getListResponse($data);
        }

        if (is_a( $response, 'Convo\Core\Adapters\Viber\ViberCommandResponse'))
        {
            $this->_logger->debug('Viber ['.$response->getText().']');
            /* @var \Convo\Core\Adapters\Viber\ViberCommandResponse  $response */
            $response->getListResponse($data);
        }
    }

    public function evaluateString( $string, $context=[]) {
        $own_params	= $this->getService()->getAllComponentParams( $this);
        return parent::evaluateString( $string, array_merge( $own_params, $context));
    }

    private function _sanitizeString($string) {
        return str_replace('&', 'and', $string);
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
