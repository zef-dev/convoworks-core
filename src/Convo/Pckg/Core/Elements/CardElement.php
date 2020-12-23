<?php declare(strict_types=1);
namespace Convo\Pckg\Core\Elements;

use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Workflow\VisualCard;
use Convo\Core\Workflow\VisualItem;

/**
 * Class CardElement
 * @package Convo\Pckg\Core\Elements
 * @deprecated
 */

class CardElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    /** @var array */
	private $_dataItem = array();

	private $_dataItemTitle;
	private $_dataItemSubtitle;
	private $_dataItemDescription1;
	private $_dataItemDescription2;
	private $_dataItemDescription3;
	private $_dataItemImageUrl;
	private $_dataItemImageText;

	private $_backButton;

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_dataItem = $properties['data_item'];

        $this->_dataItemTitle = $properties['data_item_title'];
        $this->_dataItemSubtitle = $properties['data_item_subtitle'];
        $this->_dataItemDescription1 = $properties['data_item_description_1'];
        $this->_dataItemDescription2 = $properties['data_item_description_2'];
        $this->_dataItemDescription3 = $properties['data_item_description_3'];
        $this->_dataItemImageUrl = $properties['data_item_image_url'];
        $this->_dataItemImageText = $properties['data_item_image_text'];
        $this->_backButton = $properties['back_button'];
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST;
        $params = $this->getService()->getComponentParams( $scope_type, $this);

        $params->setServiceParam('cardItem', $this->evaluateString($this->_dataItem));

        $cardDefinition = new VisualItem(
            $this->evaluateString($this->_sanitizeString($this->_dataItemTitle)),
            $this->evaluateString($this->_sanitizeString($this->_dataItemSubtitle)),
            $this->evaluateString($this->_sanitizeString($this->_dataItemDescription1)),
            $this->evaluateString($this->_dataItemImageUrl),
            $this->evaluateString($this->_sanitizeString($this->_dataItemImageText))
        );

        $data = new VisualCard($cardDefinition, []);

        $backButton	=   $this->evaluateString( $this->_backButton);
        $this->_logger->debug( 'Back Button ['.$backButton.']');

        $this->_logger->debug('Card element read method executed ['.print_r( $data, true).']');

        if (is_a( $response, 'Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse'))
        {
            $this->_logger->debug('Google action invoked with dialogflow ['.$response->getText().']');
            /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse  $response */
            $response->getCardResponse($data);
        }

        if (is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
        {
            $response->setDataCard( $data);
            $response->setBackButton( $backButton);

            $this->_logger->debug('Amazon command invoked ['.$response->getText().']');

            if ($request->getIntentType() == 'Display.ElementSelected') {
                // todo determine if card action or list item was pressed
                $params->setServiceParam('selected_option', $request->getSelectedOption());
                $response->setSelectedOption($params->getServiceParam('selected_option'));
            }

            if ($request->getIsDisplaySupported() && $request->getIsDisplayInterfaceEnabled())
            {
                /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response*/
                $response->getCardResponse($data);
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
            $response->getCardResponse($data);
        }

        if (is_a( $response, 'Convo\Core\Adapters\Viber\ViberCommandResponse'))
        {
            $this->_logger->debug('Viber ['.$response->getText().']');
            /* @var \Convo\Core\Adapters\Viber\ViberCommandResponse  $response */
            $response->getCardResponse($data);
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
