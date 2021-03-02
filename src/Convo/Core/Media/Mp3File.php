<?php


namespace Convo\Core\Media;

class Mp3File implements IAudioFile
{
    private $_fileUrl;
    private $_song;
    private $_artist;
    
    private $_artworkUrl;
    private $_backgroundUrl;
    
    public function __construct( $fileUrl, $song, $artist, $artwork=null, $background=null)
    {
        $this->_fileUrl         =   $fileUrl;
        $this->_song            =   $song;
        $this->_artist          =   $artist;
        
        $this->_artworkUrl      =   $artwork;
        $this->_backgroundUrl   =   $background;

    }

    public function getFileUrl() {
        return $this->_fileUrl;
    }
    
    public function getSongTitle() {
        return $this->_song;
    }
    
    public function getArtist() {
        return $this->_artist;
    }

    public function getSongImageUrl() {
        return $this->_artworkUrl;
    }
    
    public function getSongBackgroundUrl() {
        return $this->_backgroundUrl;
    }

    public function __toString()
    {
        return get_class( $this).'['.$this->_fileUrl.']['.$this->_song.']['.$this->_artist.']['.$this->_artworkUrl.']['.$this->_backgroundUrl.']';
    }
}

