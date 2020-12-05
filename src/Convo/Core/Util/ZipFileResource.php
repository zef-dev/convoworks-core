<?php declare(strict_types=1);

namespace Convo\Core\Util;

use ZipArchive;

class ZipFileResource implements IFileResource
{
    private $_filename;
    private $_contentType;
    private $_structure;

    private $_tempdir;

    /**
     * @var \ZipArchive
     */
    private $_archive;

    public function __construct($filename, $contentType, $data)
    {
        $this->_filename = $filename;
        $this->_contentType = $contentType;
        $this->_structure = $data;

        $this->_tempdir = sys_get_temp_dir();

        $this->_zipContents();
    }

    public function getFilename()
    {
        return $this->_filename;
    }
    
	
    public function getContentType()
    {
        return $this->_contentType;
    }
    
	
    public function getSize()
    {
        return filesize($this->_tempdir.'/'.$this->_filename);
    }
    
	
    public function getContent()
    {
        if (!$this->_archive->close()) {
            throw new \Exception('Archive could not be closed');
        }

        return file_get_contents($this->_tempdir.'/'.$this->_filename);
    }

    private function _zipContents()
    {
        $archive = new ZipArchive();
        $zipname = $this->_tempdir.'/'.$this->_filename;

        if ($archive->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE) === false) {
            throw new \Exception('Could not create ZIP archive to write into');
        }

        $dir   = null;
        $added = [];

        foreach ($this->_structure as $dirname => $data)
        {
            if ($dirname !== '.') {
                $dir = $dirname;

                if (!in_array($dir, $added)) {
                    $archive->addEmptyDir($dir);
                    $added[] = $dir;
                }
            }

            /** @var \Convo\Core\Util\IFileResource[] $data */
            foreach ($data as $file) {
                $name = $dir !== null ? $dir.'/'.$file->getFilename() : $file->getFilename();
                $archive->addFromString($name, $file->getContent());
            }
        }

        $this->_archive = $archive;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this).'['.$this->_filename.']['.$this->_contentType.']['.$this->getSize().']['.print_r($this->_structure, true).']';
    }
}