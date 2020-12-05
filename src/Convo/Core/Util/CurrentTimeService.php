<?php

namespace Convo\Core\Util;

class CurrentTimeService implements ICurrentTimeService
{
    private $_time = 0;

    public function getTime()
    {
        return $this->_time === 0 ? time() : $this->_time;
    }

    /**
     * @return \DateTimeZone
     */
    public function getTimezone(): \DateTimeZone
    {
        return new \DateTimeZone(date_default_timezone_get());
    }
}