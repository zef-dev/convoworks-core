<?php declare(strict_types=1);
namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Google\Common\IResponseType;
use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Factory\InvalidComponentDataException;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;


class GenericAplElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
    
	private $_useHashtagSign;
	private $_templateToken;
	private $_aplDefinition;

    public function __construct( $properties)
    {
        parent::__construct( $properties);

        $this->_useHashtagSign = $properties['use_hashtag_sign'] ?? false;
        $this->_templateToken  = $properties['name'];
        $this->_aplDefinition  = $properties['apl_definition'];
    }

    public function read( IConvoRequest $request, IConvoResponse $response)
    {
		$useHashtagSign = $this->evaluateString($this->_useHashtagSign);

		if (!is_bool($useHashtagSign)) {
			$useHashtagSign = false;
		}

		$aplToken = $this->evaluateString($this->_templateToken);
		if ($useHashtagSign) {
			$stringToEvaluate = $this->_prepareStringToEvaluateWithHashTag($this->_aplDefinition);
			$aplDefinition = $this->evaluateString($stringToEvaluate);
		} else {
			$aplDefinition = $this->evaluateString($this->_aplDefinition);
		}

        $aplDefinition = json_decode($aplDefinition, true);

		$this->_logger->info("Printing APL definition [" . json_encode($aplDefinition) . "]" );

        if (!$this->_isAplDefinitionValid($aplDefinition)) {
        	throw new InvalidComponentDataException('The provided APL Definition is not valid');
		}

        if ( is_a( $response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
        {
            /* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
            /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
            if ( $request->getIsDisplaySupported() && $request->getIsAplSupported()) {
				$response->prepareResponse(IAlexaResponseType::APL_RESPONSE);

				$response->setAplToken($aplToken);
                $response->setAplDefinition($aplDefinition);
            }
        }
    }

	private function _prepareStringToEvaluateWithHashTag($string) {
		$matches = [];
		$expressions = [];

		preg_match_all('/\$(\{(?:[^{}]+|(?1))+\})|#(\{(?:[^{}]+|(?1))+\})/', $string, $matches);

		if (isset($matches[0])) {
			foreach (array_unique($matches[0]) as $match) {
				$expression = trim($match);

				if (strpos($expression, '#{') !== false) {
					$expression = str_replace('#{', '${', $expression);
					$string = str_replace(trim($match), $expression, $string);
				} else if (strpos($expression, '${') !== false) {
					$expression = '${"' . $expression . '"}';
					$string = str_replace(trim($match), $expression, $string);
				}
			}
		}

		// $this->_logger->debug("Prepared output for string evaluation [" . ($string) . "]");

		return $string;
	}

	private function _isAplDefinitionValid($aplDefinition) {
    	$isValid = false;
		if (isset($aplDefinition['document'])) {
			$isValid = true;
		}
    	return $isValid;
	}
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_aplDefinition.']';
    }
}
