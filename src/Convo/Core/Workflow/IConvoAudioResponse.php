<?php


namespace Convo\Core\Workflow;


use Convo\Core\Media\Mp3File;

interface IConvoAudioResponse extends IConvoResponse
{
    public function playSong(Mp3File $song, $offset = 0) : array;

    public function enqueueSong(Mp3File $playingSong, Mp3File $enqueuingSong) : array;

    public function resumeSong(Mp3File $song, $offset) : array;

    public function stopSong() : array;

    public function emptyResponse() : array;

    public function clearQueue() : array;
}
