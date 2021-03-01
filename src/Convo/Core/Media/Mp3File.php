<?php


namespace Convo\Core\Media;

use wapmorgan\Mp3Info\Mp3Info;

class Mp3File implements IAudioFile
{
    private $_filepath;
    private $_fileUrl;
    private $_fileMetaData  =   [];

    public function __construct( $filePath, $fileUrl)
    {
        $this->_filePath    =   $filePath;
        $this->_fileUrl     =   $fileUrl;
        
        try {
            $info                   =   new Mp3Info( $filePath, true);
            $this->_fileMetaData    =   $info->tags;
        } catch ( \Exception $e) {
        }
        
        if ( !isset( $this->_fileMetaData['artist']) || empty( $this->_fileMetaData['artist'])) {
            $this->_fileMetaData['artist'] = 'N/A';
        }
        
        if ( !isset( $this->_fileMetaData['song']) || empty( $this->_fileMetaData['song'])) {
            $name   =   basename( $this->_filePath, '.mp3');
            $name   =   str_replace( "_", " ", $name);
            $name   =   str_replace( "-", " ", $name);
            $this->_fileMetaData['song']    =   $name;
        }
    }

    public function getFileUrl() : string
    {
        return $this->_fileUrl;
    }

    public function getArtist() : string {
        return $this->_fileMetaData['artist'];
    }

    public function getSongTitle() : string {
        return $this->_fileMetaData['song'];
    }

    public function __toString()
    {
        return $this->_fileUrl;
    }
}

