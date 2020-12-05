<?php


namespace Convo\Core\Adapters\Fbm;


class FacebookMessengerCommandResponse extends \Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse
{
  private $_texts =	[];
  private $_text = "";

  public function addText($text, $append = false)
  {
    parent::addText($text);
    $resultText = preg_replace('/\s\s+/', ' ', strip_tags($text));
    $this->_texts[]	= $resultText;
  }

  public function setText($text) {
    $this->_text = strip_tags($text);
  }

  public function getTexts() {
    return $this->_texts;
  }

  public function getPlatformResponse()
  {
      return $this->_textResponse();
  }

  private function _textResponse() {
      return ["text" => $this->_text];
  }
}
