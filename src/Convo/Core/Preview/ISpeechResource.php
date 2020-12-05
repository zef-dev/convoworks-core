<?php declare(strict_types=1);

namespace Convo\Core\Preview;

interface ISpeechResource
{
    /**
     * Get speech from element
     *
     * @return PreviewSpeechPart Array of speech info objects
     */
    public function getSpeech();
}