<?php


namespace Convo\Core\Workflow;


use Convo\Core\Media\IAudioFile;

interface IConvoAudioResponse extends IConvoResponse
{
    /**
     * Plays song from given offset in seconds
     * @param IAudioFile $song
     * @param int $offset
     */
    public function playSong(IAudioFile $song, $offset = 0);

    /**
     * Enqueue next song to play, while providing the old one too.
     * @param IAudioFile $playingSong
     * @param IAudioFile $enqueuingSong
     */
    public function enqueueSong(IAudioFile $playingSong, IAudioFile $enqueuingSong);

    /**
     * @param IAudioFile $song
     * @param int $offset
     * @return array
     * @deprecated
     */
    public function resumeSong(IAudioFile $song, $offset) : array;

    /**
     * Sends stop playing instruction
     */
    public function stopSong();

    /**
     * Sets empty response for the platform.
     */
    public function emptyResponse();

    /**
     * Clears the playlist queue
     */
    public function clearQueue();
}
