<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Adapters\Alexa\AmazonCommandResponse;
use Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse;

class TextResponseElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement, \Convo\Core\Preview\IBotSpeechResource
{
	const TYPE_DEFAULT	=	'default';
	const TYPE_REPROMPT	=	'reprompt';
	const TYPE_BOTH	=	'both';


    const ALEXA_EMOTION_TYPE	    =	'neutral';
    const ALEXA_EMOTION_INTENSITY	=	'medium';
    const ALEXA_DOMAIN	            =	'normal';

	private $_type;
	private $_text;
	private $_break;
	private $_alexaEmotion;
	private $_alexaEmotionIntensity;
	private $_alexaDomain;

	private $_append;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_text		            =	$properties['text'] ?? '';
		$this->_type		            =	$properties['type'] ?? self::TYPE_DEFAULT;
		$this->_break		            =	$properties['break'] ?? null;
		$this->_alexaEmotion            =   $properties['alexa_emotion'] ?? self::ALEXA_EMOTION_TYPE;
		$this->_alexaEmotionIntensity   =   $properties['alexa_emotion_intensity'] ?? self::ALEXA_EMOTION_INTENSITY;
		$this->_alexaDomain             =   $properties['alexa_domain'] ?? self::ALEXA_DOMAIN;
		$this->_append                  =   $properties['append'] ?? false;
	}

	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$this->_logger->info('Raw text ['.$this->_text.']');

		$text = $this->evaluateString($this->_text);

		if ($this->_break) {
			$text .= '<break time="'.$this->_break.'"/>';
		}

        $type = $this->evaluateString($this->_type);
		$this->_addPlatformText($response, $text, $type);
	}

	public function getSpeech()
	{
		$speech_part = new \Convo\Core\Preview\PreviewSpeechPart($this->getId());

		try {
		    $speech_part->addText( $this->getService()->previewString( $this->_text));
		} catch ( \Throwable $e) {
		    $this->_logger->notice( $e->getMessage());
		    $speech_part->addText( $this->_text);
		}

		return $speech_part;
	}

	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_type.']['.$this->_break.']['.$this->_text.']';
	}

    private function _addPlatformText($response, $text, $type): void
    {
        $append = $this->evaluateString($this->_append);

        if ($type === self::TYPE_DEFAULT)
        {
            $this->_logger->info( 'Adding text ['.$text.']');
            if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse')) {
                $domain = $this->evaluateString($this->_alexaDomain);
                $emotion = $this->evaluateString($this->_alexaEmotion);
                $emotion_intensity = $this->evaluateString($this->_alexaEmotionIntensity);

                /** @var AmazonCommandResponse $response */
                if ($domain !== self::ALEXA_DOMAIN) {
                    $response->addDomainText($domain, $text, $append);
                } else if ($emotion !== self::ALEXA_EMOTION_TYPE) {
                    $response->addEmotionText($emotion, $emotion_intensity, $text, $append);
                } else if ($emotion === self::ALEXA_EMOTION_TYPE && $domain === self::ALEXA_DOMAIN) {
                    $response->addText($text, $append);
                }
            } else {
                /* @var DefaultTextCommandResponse $response */
                $response->addText($text, $append);
            }
        } else if ($type === self::TYPE_REPROMPT) {
            $this->_logger->info( 'Adding reprompt text ['.$text.']');
            
            if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
            {
                $domain = $this->evaluateString($this->_alexaDomain);
                $emotion = $this->evaluateString($this->_alexaEmotion);
                $emotion_intensity = $this->evaluateString($this->_alexaEmotionIntensity);

                /** @var AmazonCommandResponse $response */
                if ($emotion !== self::ALEXA_EMOTION_TYPE) {
                    $response->addEmotionRepromptText($emotion, $emotion_intensity, $text, $append);
                }
                if ($domain !== self::ALEXA_DOMAIN) {
                    $response->addDomainRepromptText($domain, $text, $append);
                }
                if ($emotion === self::ALEXA_EMOTION_TYPE && $domain === self::ALEXA_DOMAIN) {
                    $response->addRepromptText($text, $append);
                }
            } else {
                /** @var DefaultTextCommandResponse $response */
                $response->addRepromptText($text, $append);
            }
        } else if ($type === self::TYPE_BOTH) {
			$this->_logger->info( 'Adding reprompt text ['.$text.']');

			if (is_a($response, 'Convo\Core\Adapters\Alexa\AmazonCommandResponse'))
			{
				$domain = $this->evaluateString($this->_alexaDomain);
				$emotion = $this->evaluateString($this->_alexaEmotion);
				$emotion_intensity = $this->evaluateString($this->_alexaEmotionIntensity);

				/** @var AmazonCommandResponse $response */
				if ($emotion !== self::ALEXA_EMOTION_TYPE) {
					$response->addEmotionText($emotion, $emotion_intensity, $text, $append);
					$response->addEmotionRepromptText($emotion, $emotion_intensity, $text, $append);
				}
				if ($domain !== self::ALEXA_DOMAIN) {
					$response->addDomainText($domain, $text, $append);
					$response->addDomainRepromptText($domain, $text, $append);
				}
				if ($emotion === self::ALEXA_EMOTION_TYPE && $domain === self::ALEXA_DOMAIN) {
					$response->addText($text, $append);
					$response->addRepromptText($text, $append);
				}
			} else {
				/** @var DefaultTextCommandResponse $response */
				$response->addText($text, $append);
				$response->addRepromptText($text, $append);
			}
		} else {
            throw new \Exception( 'Unexpected type ['.$type.']');
        }
    }
}
