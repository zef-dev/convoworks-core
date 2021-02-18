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

    /**
     * @var PreviewSection[]
     */
    private $_sections = [];

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

    public function addSection(PreviewSection $section)
    {
        if (!$section->isEmpty()) {
            $this->_sections[] = $section;
        }
    }

    public function getData()
    {
        return [
            'block_name' => $this->_blockName,
            'block_id' => $this->_blockId,
            'sections' => array_map(function ($section) { return $section->getData(); }, $this->_sections)
        ];
    }

    // UTIL
    public function __toString()
    {
        return get_class($this).'['.$this->_blockId.']';
    }
}
