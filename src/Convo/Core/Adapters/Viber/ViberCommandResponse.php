<?php


namespace Convo\Core\Adapters\Viber;


class ViberCommandResponse extends \Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse
{
    private $_texts =	[];
    private $_text = "";
    private $_receiver = "";
    private $_senderName = "";
    private $_responseType = "text";

    public function addText($text, $append = false)
    {
        parent::addText($text, $append);
        $resultText = preg_replace('/\s\s+/', ' ', strip_tags($text));
        $this->_texts[]	= $resultText;
    }

    public function setResponseType($responseType) {
        $this->_responseType = $responseType;
    }

    public function setText($text) {
        $this->_text = $text;
    }

    public function getTexts() {
        return $this->_texts;
    }

    public function setSenderName($serviceId) {
        $this->_senderName = $this->_serviceIdToName($serviceId);
    }

    public function setReceiver($sessionId) {
        $this->_receiver = $sessionId;
    }

    public function getPlatformResponse()
    {
        $response = $this->_textResponse();
        return $response;
    }

    private function _textResponse() {
      return [
          "receiver" => $this->_receiver,
          "sender" => [
              "name" => $this->_senderName
          ],
          "type" => "text",
          "text" => implode("\n\n", array_map( function ( $item) { return $item; }, $this->getTexts()))
      ];
    }

    private function _serviceIdToName($serviceId)
    {
        $str = str_replace("-", " ", $serviceId);
        $str = ucwords($str);
        return substr($str, "0", "28");
    }
}
