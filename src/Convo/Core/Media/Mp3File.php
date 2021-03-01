<?php


namespace Convo\Core\Media;


class Mp3File implements IAudioFile
{
    private $_fileName;
    private $_fileUrl;
    private $_fileMetaData;
    private $_directoryName;

    public function __construct( $fileName, $fileUrl, $fileMetaData, $directoryName)
    {
        $this->_fileName = $fileName;
        $this->_fileUrl = $fileUrl;
        $this->_fileMetaData = $fileMetaData;
        $this->_directoryName = $directoryName;
    }

    public function getFileUrl()
    {
        return $this->_fileUrl;
    }

    public function getFileName()
    {
        return str_replace("_", " ", basename($this->_fileName, '.mp3'));
    }

    public function isMetaDataAvailable() {
        return array_key_exists('artist', $this->_fileMetaData) ||
            array_key_exists('song', $this->_fileMetaData) ||
            array_key_exists('genre', $this->_fileMetaData);
    }

    public function getDirectoryName()
    {
        return $this->_directoryName;
    }


    public function getArtist() {
        return isset($this->_fileMetaData['artist']) ? $this->_fileMetaData['artist'] : $this->getDirectoryName();
    }

    public function getSongTitle() {
        return isset($this->_fileMetaData['song']) ? $this->_fileMetaData['song'] : $this->getFileName();
    }

    public function getGenre() {
        return isset($this->_fileMetaData['genre']) ? $this->_fileMetaData['genre'] : 'N/A';
    }

    public function getMetaData() {
        return [
            'artist' => $this->getArtist(),
            'song' => $this->getSongTitle()
        ];
    }

    public function isEmpty() {
        return empty($this->getArtist()) || empty($this->getSongTitle());
    }

    public function __toString()
    {
        return $this->_fileUrl;
    }
}

