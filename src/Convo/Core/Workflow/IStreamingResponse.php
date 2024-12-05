<?php

declare(strict_types=1);

namespace Convo\Core\Workflow;

interface IStreamingResponse extends IConvoResponse
{
    /**
     * Begin the streaming process.
     */
    public function startStreaming(): void;

    /**
     * Add streamed content incrementally.
     *
     * @param string $content
     */
    public function streamContent(string $content): void;

    /**
     * End the streaming process.
     */
    public function endStreaming(): void;
}
