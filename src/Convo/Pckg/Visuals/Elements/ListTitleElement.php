<?php declare(strict_types=1);
namespace Convo\Pckg\Visuals\Elements;

use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;


class ListTitleElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
    
	private $_listTitle;
	private $_listTemplate;

    public function __construct( $properties)
    {
        parent::__construct( $properties);

        $this->_listTitle       =   $properties['list_title'];
        $this->_listTemplate    =   $properties['list_template'];
    }

    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $listTitle      =   $this->evaluateString( $this->_listTitle);
        $listTemplate   =   $this->evaluateString( $this->_listTemplate);

        $data = array(
            "list_title" => $listTitle,
            "list_template" => $listTemplate,
            "list_items" => [],
        );

        if ( is_a( $response, 'Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse'))
        {
            /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse  $response */
            $response->prepareResponse( IResponseType::LIST, $data);
        }
        else if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
        {
            /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
            $response->setDataList( $data);

            if ( $request->getIsDisplaySupported() && $request->getIsDisplayInterfaceEnabled()) {
                $response->prepareResponse( IAlexaResponseType::LIST_RESPONSE);
            } else {
                $this->_logger->info( 'Display is not supported on this device ['.$request.']');
            }
        }
    }

    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_listTitle.']['.$this->_listTemplate.']';
    }
}
