<?php declare(strict_types=1);

namespace Convo\Core\Preview;

class PreviewBlock implements \Psr\Log\LoggerAwareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    private $_blockName;
    private $_blockId;

    private $_speech;

    public function __construct($blockName, $blockId)
    {
        $this->_logger = new \Psr\Log\NullLogger();

        $this->_blockName = $blockName;
        $this->_blockId = $blockId;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * @param \Convo\Core\Preview\ISpeechResource[] $sources
     * @param string $kind
     * @return void
     */
    public function collectKind($sources, $kind)
    {
        if (!isset($this->_speech[$kind])) {
            $this->_speech[$kind] = [];
        }

        foreach ($sources as $source) {
            $speech_data = $source->getSpeech()->getData();
            // $this->_logger->debug('Got speech ['.print_r($speech_data, true).']');

            $this->_speech[$kind][] = $speech_data;
        }
    }

    public function getData()
    {
        return array_merge(
            [
                'block_name' => $this->_blockName,
                'block_id' => $this->_blockId
            ],
            $this->_speech
        );
    }

    // UTIL
    public function __toString()
    {
        return get_class($this).'['.$this->_blockId.']';
    }
}
