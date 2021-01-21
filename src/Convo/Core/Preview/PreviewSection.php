<?php declare(strict_types=1);

namespace Convo\Core\Preview;

class PreviewSection
{
    private $_name;

    /**
     * @var PreviewUtterance[]
     */
    private $_utterances = [];

    public function __construct($name)
    {
        $this->_name = $name;
    }

    public function addUtterance(PreviewUtterance $utterance)
    {
        $this->_utterances[] = $utterance;
    }

    public function getData()
    {
        return [
            'name' => $this->_name,
            'utterances' => array_map(function ($utterance) { return $utterance->getData(); }, $this->_utterances)
        ];
    }
}
