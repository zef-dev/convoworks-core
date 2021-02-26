<?php


namespace Convo\Core\Workflow;


use Convo\Core\Media\Mp3File;

interface IConvoAudioResponse extends IConvoResponse
{
    /**
     * Plays song from given offset in seconds
     * @param Mp3File $song
     * @param int $offset
     */
    public function playSong(Mp3File $song, $offset = 0);

    /**
     * Enqueue next song to play, while providing the old one too.
     * @param Mp3File $playingSong
     * @param Mp3File $enqueuingSong
     */
    public function enqueueSong(Mp3File $playingSong, Mp3File $enqueuingSong);

    /**
     * @param Mp3File $song
     * @param int $offset
     * @return array
     * @deprecated
     */
    public function resumeSong(Mp3File $song, $offset) : array;

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
