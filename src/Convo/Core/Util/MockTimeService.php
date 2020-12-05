<?php

namespace Convo\Core\Util;

class MockTimeService implements ICurrentTimeService
{
    private $_time = 0;
    private $_timezone;

    public function setTime($time)
    {
        $this->_time = $time;
    }

    public function getTime()
    {
        return $this->_time === 0 ? time() : $this->_time;
    }

    public function setTimezone($timezone) {
        $this->_timezone = $timezone;
    }

    /**
     * @return \DateTimeZone
     */
    public function getTimezone(): \DateTimeZone
    {
        return $this->_timezone;
    }
}