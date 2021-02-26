<?php

namespace Convo\Core\Workflow;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Media\Mp3File;

interface IMediaSourceContext extends IServiceContext
{
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
    public function isLast();
    
    /**
     * Returns current results total count.
     * @return int
     */
    public function getCount();
    
    /**
     * Returns the next song if available and sets the pointer. Will throw DataItemNotFoundException if no results, or single result with loop status off
     * @return Mp3File
     * @throws DataItemNotFoundException
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
