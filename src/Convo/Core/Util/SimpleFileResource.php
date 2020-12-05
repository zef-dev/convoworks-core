<?php declare(strict_types=1);

namespace Convo\Core\Util;

class SimpleFileResource implements IFileResource
{	
    private $_filename;
    private $_contentType;
    private $_content;
    
	public function __construct( $filename, $contentType, $content)
	{
	    $this->_filename       =   $filename;
	    $this->_contentType    =   $contentType;
	    $this->_content        =   $content;
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
        return strlen( $this->getContent());
    }
	
    public function getContent()
    {
        return $this->_content;
    }
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->_filename.']['.$this->_contentType.']['.$this->getSize().']';
	}
}