<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;


/**
 * Class FileReader
 * @package Convo\Pckg\Core\Elements
 * @deprecated
 */
class FileReader extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_basePath;
        private $_mode;
        private $_var;

	public function __construct( $properties)
	{
            parent::__construct( $properties);

            $this->_basePath    =   $properties['basePath'];
            $this->_mode        =   $properties['mode'];
            $this->_var         =   $properties['var'];
	}

	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
            $this->_logger->debug( 'Raw basePath ['.$this->_basePath.']');
            $this->_logger->debug( 'Raw mode ['.$this->_mode.']');
            $this->_logger->debug( 'Raw var ['.$this->_var.']');

            $basePath	=   $this->evaluateString( $this->_basePath);
            $mode	=   $this->evaluateString( $this->_mode);
            $var	=   $this->evaluateString( $this->_var);

            $this->_logger->debug( 'Using basePath ['.$basePath.']');
            $this->_logger->debug( 'mode is ['.$mode.']');
            $this->_logger->debug( 'var to store ['.$var.']');

            $response->addText( 'Reading folder ['.$basePath.']');

            $items = array();
            $folder = dir($basePath);

            while (false !== ($entry = $folder->read()))
            {
            	$this->_logger->debug( 'Checking entry ['.$entry.']');

                if ($entry != '.' && $entry != '..')
                {
                    if ( $mode === 'folders')
                    {
                	if ( is_dir( $basePath . '/' .$entry))
                	{
                            $items[] = $entry;
                	}
                    }
                    else
                    {
                	if (is_file($basePath . '/' .$entry))
                	{
                            $items[] = $entry;
                	}
                    }
                }
            }

            $this->_logger->debug( 'Found ['.count( $items).']');

            $this->getBlockParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST)->setServiceParam( $var, $items);
	}

	// UTIL
	public function __toString()
	{
            return parent::__toString().'['.$this->_basePath.']['.$this->_mode.']['.$this->_var.']';
	}
}
