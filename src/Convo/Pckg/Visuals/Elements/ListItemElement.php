<?php declare(strict_types=1);
namespace Convo\Pckg\Visuals\Elements;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class ListItemElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_listItemKey;
	private $_listItemTitle;
	private $_listItemDescription1;
	private $_listItemDescription2;
	private $_listItemImageUrl;
	private $_listItemImageText;

    public function __construct( $properties)
    {
        parent::__construct( $properties);

        $this->_listItemKey             =   $properties['list_item_key'];
        $this->_listItemTitle           =   $properties['list_item_title'];
        $this->_listItemDescription1    =   $properties['list_item_description_1'];
        $this->_listItemDescription2    =   $properties['list_item_description_2'];
        $this->_listItemImageUrl        =   $properties['list_item_image_url'];
        $this->_listItemImageText       =   $properties['list_item_image_text'];
    }

    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $item = array(
            "list_item_key" => $this->evaluateString( $this->_listItemKey),
            "list_item_title" => $this->evaluateString( $this->_listItemTitle),
            "list_item_description_1" => $this->evaluateString( $this->_listItemDescription1),
            "list_item_description_2" => $this->evaluateString( $this->_listItemDescription2),
            "list_item_image_url" => $this->evaluateString( $this->_listItemImageUrl),
            "list_item_image_text" => $this->evaluateString( $this->_listItemImageText),
        );

        if (is_a( $response, 'Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse'))
        {
            /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandRequest  $request */
            /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse  $response */
            if ( $request->getIsDisplaySupported()) {
                $response->addListItem( $item);
            }
        }

        if (is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
        {
            /* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
            /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
            if ( $request->getIsDisplaySupported() && $request->getIsDisplayInterfaceEnabled()) {
                $response->addListItem( $item);
            }
        }
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_listItemKey.']['.$this->_listItemTitle.']';
    }
}
