<?php

namespace Convo\Core\Workflow;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Media\Mp3File;

interface IMediaSourceContext extends IServiceContext
{
    /**
     * Returns all songs of an source.
     * @return Mp3File[]
     * @deprecated
     */
    public function list() : iterable;

    /**
     * Performs the search operation by assoc array.
     * @return iterable
     * @throws DataItemNotFoundException
     * @deprecated
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
     * Returns the previous song if available and sets the pointer.
     * @return Mp3File
     * @deprecated
     */
    public function previous() : Mp3File;

    /**
     * Returns the first song of the list and sets the pointer to 0.
     * @return Mp3File
     * @deprecated
     */
    public function first() : Mp3File;

    /**
     * Returns the last song of the list and sets the pointer to the last items index.
     * @return Mp3File
     * @deprecated
     */
    public function last() : Mp3File;

    /**
     * Moves the current song index to an specified index.
     * @param $index
     * @return mixed
     * @deprecated  
     */
    public function movePointerTo($index);

    /**
     * Returns the current specified song index.
     * @return int
     * @deprecated     
     */
    public function getPointerPosition() : int;

    /**
     * Decides if the pointer should be moved when next, previous, first or last is called
     * @param bool $shouldMovePointer
     * @return mixed
     * @deprecated
     */
    public function setShouldMovePointer($shouldMovePointer = true);
    
    
    

    // STATE
    
    /**
     * Are there any results
     * @return bool
     */
    public function isEmpty();
    
    /**
     * Is there next available.
     * @return bool
     */
    public function hasNext();
    
    /**
     * Returns the next song if available and sets the pointer.
     * @return Mp3File
     */
    public function next() : Mp3File;
    
    /**
     * Returns the current mp3 file. Will throw DataItemNotFoundException if list is empty.
     * @return Mp3File
     * @throws DataItemNotFoundException
     */
    public function current() : Mp3File;
    
    
    
    // ACTIONS
    /**
     * Moves pointer to the previous song. If result is empty or we have single result with loop off, will throw DataItemNotFoundException
     * @throws DataItemNotFoundException
     */
    public function movePrevious();

    /**
     * Moves pointer to the next song. If result is empty or we have single result with loop off, will throw DataItemNotFoundException
     * @throws DataItemNotFoundException
     */
    public function moveNext();
    
    
    // SETTINGS
    /**
     * Returns the current song offset in milliseconds.
     * @return int
     */
    public function getOffset() : int;
    
    /**
     * Sets the current song offset in milliseconds.
     * @param int $offset
     */
    public function setOffset( $offset);
    
    /**
     * Sets the status of the loop playback mode.
     * @param bool $loopStatus
     */
    public function setLoopStatus( $loopStatus);
    
    /**
     * Returns the status of the loop playback mode.
     * @return bool
     */
    public function getLoopStatus() : bool;

}
