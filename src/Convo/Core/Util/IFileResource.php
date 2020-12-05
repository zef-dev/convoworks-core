<?php declare(strict_types=1);

namespace Convo\Core\Util;

interface IFileResource
{	
	
    public function getFilename();
    
	
    public function getContentType();
    
	
    public function getSize();
    
	
    public function getContent();
    
}