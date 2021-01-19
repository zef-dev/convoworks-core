<?php declare(strict_types=1);

namespace Convo\Core\Preview;

class PreviewUtterance
{
    private $_text;
    private $_isBot;
    private $_intentSource;

    public function __construct($text, $isBot = true, $intentSource = null)
    {
        $this->_text = $text;
        $this->_isBot = $isBot;
        $this->_intentSource = $intentSource;
    }

    public function getText()
    {
        return $this->_text;
    }

    public function isBot()
    {
        return $this->_isBot;
    }

    public function getIntentSource()
    {
        return $this->_intentSource;
    }

    public function getData()
    {
        return [
            'text' => $this->_text,
            'is_bot' => $this->_isBot,
            'intent' => $this->_intentSource
        ];
    }
}
