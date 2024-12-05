<?php

declare(strict_types=1);

namespace Convo\Core\Adapters\ConvoChat;

class DefaultTextCommandResponse implements \Convo\Core\Workflow\IConvoResponse
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    private $_texts         =    array();
    private $_reprompts     =    array();
    private $_endSession    =    false;
    private $_streaming     =    false;

    public function __construct()
    {
        $this->_logger = new \Psr\Log\NullLogger();
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }


    public function enableStreaming()
    {
        $this->_streaming = true;
    }

    public function addText($text, $append = false)
    {
        if ($text === null) {
            $text = '';
        }
        if ($this->_streaming) {
            // Stream the text chunk directly
            $this->_streamText($text);
        } else {
            // Fallback: Store the text in the internal array
            if ($append && !empty($this->_texts)) {
                $this->_texts[count($this->_texts) - 1] .= ' ' . $text;
            } else {
                $this->_texts[] = $text;
            }
        }
    }

    private function _streamText($text)
    {
        // Output the text chunk as a streamed response
        echo "data: " . json_encode(['text_response' => $text]) . "\n\n";

        // Flush the output buffer to send data immediately
        // if (ob_get_length()) {
        ob_flush();
        // }
        flush();

        // Log the streamed text
        $this->_logger->info('Streamed text: ' . $text);
    }

    public function setShouldEndSession($endSession)
    {
        $this->_endSession    =    $endSession;
    }

    public function shouldEndSession()
    {
        return $this->_endSession;
    }

    public function isEmpty()
    {
        return empty($this->_texts);
    }

    public function isSsml()
    {
        if (empty($this->_textsSsml) === true) {
            return false;
        }
        return true;
    }

    // SPEECH
    // public function addText($text, $append = false)
    // {
    //     if ($text === null) {
    //         $text = '';
    //     }

    //     if ($append && count($this->_texts) > 0) {
    //         $this->_appendText($text, $this->_texts);
    //     } else {
    //         $this->_texts[]    =    $text;
    //     }
    // }

    public function getText()
    {
        return preg_replace('/\s\s+/', ' ', strip_tags($this->getTextSsml()));
    }

    public function getTextSsml()
    {
        if (count($this->_texts) > 0) {
            $last = count($this->_texts) - 1;

            if (stripos($this->_texts[$last], '</p>') === false) {
                $this->_texts[$last] = $this->_texts[$last] . '</p>';
            }
        }

        return '<speak>' . preg_replace('/\s\s+/', ' ', implode(" ", $this->_texts)) . '</speak>';
    }

    // REPROMPT
    public function addRepromptText($text, $append = false)
    {
        if ($append && count($this->_reprompts) > 0) {
            $this->_appendText($text, $this->_reprompts);
        } else {
            $this->_reprompts[]    =    $text;
        }
    }

    public function getRepromptText()
    {
        return strip_tags($this->getRepromptTextSsml());
    }

    public function getRepromptTextSsml()
    {
        return '<speak>' . implode(" ", $this->_reprompts) . '</speak>';
    }

    public function getPlatformResponse()
    {
        // 		"text_response":"Welcome to \"Random Number\" game. We have two type of games. You can play \"guess the number\", where you are guessing the number I picked, or, you can play \"pick the number\", where I am the one guessing it. Would you like to guess or to pick the number",
        // 		"text_reprompt":"Please say, which game type would you like to play? Guess, or pick the number",
        // 		"should_end_session":false}
        $response    =    [
            'text_responses' => array_map(function ($item) {
                return $item;
            }, $this->_texts),
            'text_reprompts' => array_map(function ($item) {
                return $item;
            }, $this->_reprompts),
            'should_end_session' => $this->shouldEndSession(),
        ];
        return $response;
    }

    // COMMON
    private function _clearWrappers($text)
    {
        $text    =    str_ireplace('<speak>', '', $text);
        $text    =    str_ireplace('</speak>', '', $text);
        $text    =    str_ireplace('<p>', '', $text);
        $text    =    str_ireplace('</p>', '', $text);
        return $text;
    }

    private function _appendText($text, &$array)
    {
        $preceding = array_pop($array);
        $preceding = $preceding . ' ' . $text;
        $array[] = $preceding;
    }

    // UTIL
    public function __toString()
    {
        $str    =    '';
        if (!empty($this->_texts)) {
            $str    .=    '[' . implode(" ", $this->_texts) . ']';
        }
        if (!empty($this->_reprompts)) {
            $str    .=    '[' . implode(" ", $this->_reprompts) . ']';
        }
        return get_class($this) . $str;
    }
}
