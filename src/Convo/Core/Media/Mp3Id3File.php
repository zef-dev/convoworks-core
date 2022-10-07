<?php


namespace Convo\Core\Media;

use wapmorgan\Mp3Info\Mp3Info;

class Mp3Id3File extends Mp3File
{
    private $_filePath;
    private $_fileMetaData;
    
    public function __construct( $filePath, $fileUrl, $artwork=null, $background=null, $song=null, $artist=null)
    {
        parent::__construct( $fileUrl, $song, $artist, $artwork, $background);
        
        $this->_filePath        =   $filePath;
    }
    
    public function getMetadata()
    {
        if ( !isset( $this->_fileMetaData) && !empty( $this->_filePath))
        {
            try {
                $info                   =   new Mp3Info( $this->_filePath, true);
                $this->_fileMetaData    =   $info->tags;
            } catch ( \Exception $e) {
                $this->_fileMetaData    =   [];
            }
            
            if ( !isset( $this->_fileMetaData['artist']) || empty( $this->_fileMetaData['artist'])) {
                $this->_fileMetaData['artist'] = '';
            }
    
            if ( !isset( $this->_fileMetaData['song']) || empty( $this->_fileMetaData['song'])) {
                $name   =   basename( $this->_filePath, '.mp3');
                $name   =   str_replace( "_", " ", $name);
                $name   =   str_replace( "-", " ", $name);
                $name   =   preg_replace( '/\s+/', ' ', $name);
                $this->_fileMetaData['song']    =   $name;
            }
        }
        
        return $this->_fileMetaData;
    }
    
    public function getSongTitle() {
        $song = $this->getMetadata()['song'] ?? parent::getSongTitle();
        if (empty($song)) {
            $song = parent::getSongTitle();
        }
        return $song;
    }
    
    public function getArtist() {
        $artist = $this->getMetadata()['artist'] ?? parent::getArtist();
        if (empty($artist)) {
            $artist = parent::getArtist();
        }
        return $artist;
    }

    public function __toString()
    {
        return parent::__toString().'['.$this->_filePath.']';
    }
}

