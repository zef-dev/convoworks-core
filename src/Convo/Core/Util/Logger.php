<?php

namespace Convo\Core\Util;

use \Psr\Log\LogLevel;

class Logger extends \Psr\Log\AbstractLogger implements \Psr\Log\LoggerInterface
{
    const LOG_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    private $_path;
    private $_prefix;
    private $_level;
    private $_useSession;

    private $_hash;

    public function __construct( $path, $prefix, $level=\Psr\Log\LogLevel::DEBUG, $useSession = false)
    {
        $this->_path = \Convo\Core\Util\StrUtil::removeTrailingSlashes( $path).'/';
        $this->_prefix = $prefix;
        $this->_level = $level;
        $this->_useSession = $useSession;

//         $this->_hash = 'pid '.getmypid();
        $this->_hash		=	strtoupper( bin2hex( random_bytes( 5)));
        
        if (!is_dir($this->_path)) {
            if (mkdir($this->_path) === false) {
                throw new \Exception('Could not create log at ['.$this->_path.']. Please create it manually, and/or make sure PHP has permission to write in that directory.');
            }
        }

        $this->debug( '============================================================');
        if (isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST'])) {
            $this->debug( $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $this->debug( 'Content-Type: '.$_SERVER['CONTENT_TYPE']);
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->debug( 'User-Agent: '.$_SERVER['HTTP_USER_AGENT']);
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $this->debug( 'IP: '.$_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        else if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->debug( 'IP: '.$_SERVER['REMOTE_ADDR']);
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->debug( 'Method: '.$_SERVER['REQUEST_METHOD']);
        }

        $this->debug( '============================================================');
    }

    /**
     * Logs a message or exception to the created log file.
     *
     * @param string $level
     * @param string|\Exception $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $index = array_search($level, self::LOG_LEVELS);
        $current = array_search( $this->_level, self::LOG_LEVELS);

        if ($current < $index) {
            return;
        }

        if ($message instanceof \Exception) {
            return $this->_logError($level, $message);
        }

        $backtrace = debug_backtrace();
        $trace = @$backtrace[1];

        if (isset($backtrace[2]['class']) && trim($backtrace[2]['class'])) {
            $info = '['.$backtrace[2]['class'].':'.$backtrace[2]['function'].'('.$backtrace[1]['line'].")]\t";
        } else {
            $info = '['.$trace['file'].'  ('.$trace['line'] .")]\t";
        }

        $str1 = $this->_getInfo($level, $info);

        $str1 .= ' '.str_replace("\r\n", "\r\n\t", $message) . "\r\n";

        $this->_log($str1);
    }

    private function _log($message)
    {
    	error_log( $message, 3, $this->_getFilename($this->_path, $this->_prefix));
    }

    private function _logError($level, \Exception $error, $depth = 0)
    {
        $str = $this->_formatError($error);

        $arr = explode("\n", trim($str));

        foreach ($arr as $item) {
            $this->log($level, str_repeat("\t", $depth).trim($item));
        }

        if ($error->getPrevious()) {
            $this->_logError($level, $error->getPrevious(), $depth + 1);
        }
    }

    private function _getInfo($level, $info)
    {
        $time = microtime();
        $date = date('H:i:s').':'.substr($time, 2, 3);

        if ($this->_useSession) {
            $sid = session_id();
            $str = "$date $level\t{$this->_hash} \{$sid\}\t$info";
        } else {
            $str = "$date $level\t{$this->_hash} $info";
        }

        return $str;
    }

    private function _formatError(\Exception $error)
    {
        if ($error instanceof \Exception) {
            $str = get_class($error).': '.$error->getMessage()."\r\n";
            $str .= $error->getTraceAsString();
        } else {
            $str = $error;
        }

        return $str;
    }

    private function _getFilename($path = '', $root = '')
    {
        return $path.$root.'_'.date('Y-m-d').'.log';
    }
}