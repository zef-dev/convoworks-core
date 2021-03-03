<?php declare(strict_types=1);
namespace Convo\Pckg\Visuals\Elements;

use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Adapters\Alexa\IAlexaResponseType;

/**
 * Class CardElement
 * @package Convo\Pckg\Visuals\Elements
 */

class CardElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_dataItemTitle;
	private $_dataItemSubtitle;
	private $_dataItemDescription1;
	private $_dataItemDescription2;
	private $_dataItemDescription3;
	private $_dataItemImageUrl;
	private $_dataItemImageText;

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_dataItemTitle = $properties['data_item_title'];
        $this->_dataItemSubtitle = $properties['data_item_subtitle'];
        $this->_dataItemDescription1 = $properties['data_item_description_1'];
        $this->_dataItemDescription2 = $properties['data_item_description_2'];
        $this->_dataItemDescription3 = $properties['data_item_description_3'];
        $this->_dataItemImageUrl = $properties['data_item_image_url'];
        $this->_dataItemImageText = $properties['data_item_image_text'];
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST;
        $params = $this->getService()->getComponentParams( $scope_type, $this);

        $data = array(
            "data_item_title" => $this->evaluateString($this->_dataItemTitle),
            "data_item_subtitle" => $this->evaluateString($this->_dataItemSubtitle),
            "data_item_description_1" => $this->evaluateString($this->_dataItemDescription1),
            "data_item_description_2" => $this->evaluateString($this->_dataItemDescription2),
            "data_item_description_3" => $this->evaluateString($this->_dataItemDescription3),
            "data_item_image_url" => $this->evaluateString($this->_dataItemImageUrl),
            "data_item_image_text" => $this->evaluateString($this->_dataItemImageText),
        );

        $this->_logger->debug('Card element read method executed ['.print_r( $data, true).']');

        if (is_a( $response, 'Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse'))
        {
            $this->_logger->debug('Google action invoked with dialogflow ['.$response->getText().']');
            /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse  $response */
            $response->prepareResponse(IResponseType::BASIC_CARD, $data);
        }

        // todo add handling for gactions and alexa
        if (is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
        {
            $response->setDataCard( $data);
            $response->setBackButton( 'HIDDEN');

            $this->_logger->debug('Amazon command invoked ['.$response->getText().']');

            if ($request->getIntentType() == 'Display.ElementSelected') {
                $params->setServiceParam('selected_option', $request->getSelectedOption());
                $response->setSelectedOption($params->getServiceParam('selected_option'));
            }

            if ($request->getIsDisplaySupported() && $request->getIsDisplayInterfaceEnabled())
            {
                /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response*/
                $response->prepareResponse(IAlexaResponseType::CARD_RESPONSE);
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
