<?php


namespace Convo\Core\Media;

use wapmorgan\Mp3Info\Mp3Info;

class Mp3File implements IAudioFile
{
    private $_filePath;
    private $_fileMetaData;
    
    private $_fileUrl;
    private $_artworkUrl;
    private $_backgroundUrl;

    private $_song;
    private $_artist;
    
    public function __construct( $filePath, $fileUrl, $artwork=null, $background=null, $song=null, $artist=null)
    {
        $this->_filePath        =   $filePath;
        
        $this->_fileUrl         =   $fileUrl;
        $this->_artworkUrl      =   $artwork;
        $this->_backgroundUrl   =   $background;

        $this->_song            =   $song;
        $this->_artist          =   $artist;
    }
    
    public function getMetadata()
    {
        if ( !isset( $this->_fileMetaData) && !empty( $this->getFilePath()))
        {
            try {
                $info                   =   new Mp3Info( $this->getFilePath(), true);
                $this->_fileMetaData    =   $info->tags;
            } catch ( \Exception $e) {
                $this->_fileMetaData    =   [];
            }
            
            if ( !isset( $this->_fileMetaData['artist']) || empty( $this->_fileMetaData['artist'])) {
                $this->_fileMetaData['artist'] = 'N/A';
            }
    
            if ( !isset( $this->_fileMetaData['song']) || empty( $this->_fileMetaData['song'])) {
                $name   =   basename( $this->getFilePath(), '.mp3');
                $name   =   str_replace( "_", " ", $name);
                $name   =   str_replace( "-", " ", $name);
                $this->_fileMetaData['song']    =   $name;
            }
        }
        
        return $this->_fileMetaData;
    }

    public function getFileUrl() {
        return $this->_fileUrl;
    }
    
    public function getSongTitle() {
        if ( $this->_song) {
            return $this->_song;
        }
        return $this->getMetadata()['song'];
    }
    
    public function getArtist() {
        if ( $this->_artist) {
            return $this->_artist;
        }
        return $this->getMetadata()['artist'];
    }

    public function getSongImageUrl() {
        return $this->_artworkUrl;
    }
    
    public function getSongBackgroundUrl() {
        return $this->_backgroundUrl;
    }

    public function __toString()
    {
        return get_class( $this).'['.$this->_filePath.']['.$this->_fileUrl.']';
    }
}

