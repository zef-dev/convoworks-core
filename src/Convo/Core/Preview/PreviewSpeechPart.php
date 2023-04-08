<?php declare(strict_types=1);

namespace Convo\Core\Preview;

class PreviewSpeechPart
{
    /**
     * @var string
     */
    private $_componentId;

    /**
     * @var string
     */
    private $_fragmentId;

    /**
     * @var array
     */
    private $_text = [];

    private $_intentSource;

    public function __construct($componentId, $fragmentId = null)
    {
        $this->_componentId = $componentId;
        $this->_fragmentId = $fragmentId;
    }

    public function getComponentId()
    {
        return $this->_componentId;
    }

    public function getFragmentId()
    {
        return $this->_fragmentId;
    }

    public function getText()
    {
        return $this->_text;
    }

    public function setIntentSource($intentSource)
    {
        $this->_intentSource = $intentSource;
    }

    public function getIntentSource()
    {
        return $this->_intentSource;
    }

    public function addText($text)
    {
        $this->_text[] = $text;
    }

    public function getData()
    {
        $data = [
            'component_id' => $this->_componentId,
        ];

        if ($this->_fragmentId) {
            $data['fragment_id'] = $this->_fragmentId;
        }

        $data['text'] = [];
        foreach ($this->_text as $text) {
            if ( !empty( $text)) {
                $data['text'][] = $text;
            }
        }

        return $data;
    }

    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
