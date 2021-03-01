<?php


namespace Convo\Core\Media;


interface IAudioFile
{

    
    /**
     * Returns full url to the audio file
     * @return string
     */
    public function getFileUrl();
    
    /**
     * Returns song title
     * @return string
     */
    public function getArtist();
    
    /**
     * Returns song artist
     * @return string
     */
    public function getSongTitle();
    
    /**
     * Returns url for the song image or null if not exists
     * @return string
     */
    public function getSongImageUrl();
    
    /**
     * Returns url for the song background image or null if not exists
     * @return string
     */
    public function getSongBackgroundUrl();
}

