<?php


namespace Convo\Core\Media;


interface IAudioFile
{

    
    public function getFileUrl() : string;
    
    public function getFileName() : string;
    
    
    public function getArtist() : string;
    
    public function getSongTitle() : string;
    
    public function getGenre() : string;
    
    
    
    /**
     * @deprecated
     */
    public function isMetaDataAvailable() : bool;
    
    /**
     * @deprecated
     */
    public function getMetaData() : array;
    
    
    /**
     * @deprecated
     */
    public function getDirectoryName() : string;
    
    /**
     * @deprecated
     */
    public function isEmpty() : bool;
    
}

