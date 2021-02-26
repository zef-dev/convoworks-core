<?php

namespace Convo\Core\Workflow;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Media\Mp3File;

interface IMediaSourceContext extends IServiceContext
{
    /**
     * Returns all songs of an source.
     * @return Mp3File[]
     */
    public function list() : iterable;

    /**
     * Performs the search operation by assoc array.
     * @return iterable
     * @throws DataItemNotFoundException
     */
    public function find() : iterable;

    /**
     * Sets the search query from provided slots.
     * @param $searchQuery
     * @return mixed
     * @deprecated
     */
    public function setSearchQuery($searchQuery);

    /**
     * Returns the current mp3 file.
     * @return Mp3File
     */
    public function current() : Mp3File;

    /**
     * Returns the next song if available and sets the pointer.
     * @return Mp3File
     */
    public function next() : Mp3File;

    /**
     * Returns the previous song if available and sets the pointer.
     * @return Mp3File
     */
    public function previous() : Mp3File;

    /**
     * Returns the first song of the list and sets the pointer to 0.
     * @return Mp3File
     */
    public function first() : Mp3File;

    /**
     * Returns the last song of the list and sets the pointer to the last items index.
     * @return Mp3File
     */
    public function last() : Mp3File;

    /**
     * Sets the song offset in milliseconds.
     * @param $offset
     */
    public function setOffset($offset);

    /**
     * Returns the song offset in milliseconds.
     * @return int
     */
    public function getOffset() : int;

    /**
     * Moves the current song index to an specified index.
     * @param $index
     * @return mixed
     */
    public function movePointerTo($index);

    /**
     * Returns the current specified song index.
     * @return int
     */
    public function getPointerPosition() : int;

    /**
     * Sets the status of the loop playback mode.
     * @param $loopStatus
     * @return mixed
     */
    public function setLoopStatus($loopStatus);

    /**
     * Returns the status of the loop playback mode.
     * @return bool
     */
    public function getLoopStatus() : bool;

    /**
     * Decides if the pointer should be moved when next, previous, first or last is called
     * @param bool $shouldMovePointer
     * @return mixed
     */
    public function setShouldMovePointer($shouldMovePointer = true);
}
