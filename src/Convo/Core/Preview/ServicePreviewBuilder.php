<?php

declare(strict_types=1);

namespace Convo\Core\Preview;


class ServicePreviewBuilder implements \Psr\Log\LoggerAwareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    private $_serviceId;

    /**
     * @var \Convo\Core\Preview\PreviewBlock[]
     */
    private $_blocks;

    public function __construct($serviceId)
    {
        $this->_serviceId = $serviceId;
        $this->_logger = new \Psr\Log\NullLogger();
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function addPreviewBlock(PreviewBlock $block, $isFragment = false)
    {
        $this->_blocks[] = ['data' => $block, 'is_fragment' => $isFragment];
    }

    public function getPreview()
    {
        $preview = [
            'service_id' => $this->_serviceId,
            'blocks' => []
        ];

        foreach ($this->_blocks as $block)
        {
            $preview['blocks'][] = ['data' => $block['data']->getData(), 'is_fragment' => $block['is_fragment']];
        }

        return $preview;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
