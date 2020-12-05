<?php declare(strict_types=1);

namespace Convo\Pckg\Dialogflow\Elements;


class SetSuggestionsElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_value;
	
	public function __construct( $config)
	{
		parent::__construct( $config);
		$this->_value = $config['value'];
	}
	
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$parsed 	=   $this->evaluateString( $this->_value);
        $suggestions  = $this->_value;
        $output = [];

        if (!empty($suggestions)) {
            $pieces = explode(";", $suggestions);
            foreach ($pieces as $suggestion) {
                $obj = array(
                    "title" => $suggestion
                );
                array_push($output, $obj);
            }

            $this->_logger->debug( 'Setting suggestions ['.print_r($output, true).'] got from ['.$this->_value.']');

            if (is_a( $response, 'Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse'))
            {
                /* @var \Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse  $response */
                $response->setSuggestions($output);
            }
        } else {
            $this->_logger->debug( 'Could not set suggestions ['.$parsed.'] got from ['.$this->_value.']');
        }
	}
	
	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_value.']';
	}
}