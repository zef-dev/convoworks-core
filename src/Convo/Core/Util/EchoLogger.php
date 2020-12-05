<?php

namespace Convo\Core\Util;


class EchoLogger extends \Psr\Log\AbstractLogger
{

    /**
     * {@inheritDoc}
     * @see \Psr\Log\LoggerInterface::log()
     */
    public function log( $level, $message, array $context = array())
    {
        $time = microtime();
        $date = date( "H:i:s").':'.substr( $time, 2, 4);
        echo $date." ".strtoupper( $level)."\t". $message."\r\n";
    }
}