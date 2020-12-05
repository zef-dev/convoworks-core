<?php


namespace Convo\Core\Util;


interface ICurrentTimeService
{
    public function getTime();

    public function getTimezone();
}