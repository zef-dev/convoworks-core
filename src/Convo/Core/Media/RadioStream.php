<?php

namespace Convo\Core\Media;

class RadioStream implements IRadioStream
{

    private $_radioStreamUrl;
    private $_radioStationName;
    private $_radioStationSlogan;

    private $_radioStationLogoUrl;

    public function __construct($radioStationUrl, $radioStationName, $radioStationSlogan, $radioStationLogoUrl = null)
    {
        $this->_radioStreamUrl         =   $radioStationUrl;
        $this->_radioStationName            =   $radioStationName;
        $this->_radioStationSlogan          =   $radioStationSlogan;

        $this->_radioStationLogoUrl      =   $radioStationLogoUrl;
    }

    public function getRadioStreamUrl()
    {
        return $this->_radioStreamUrl;
    }

    public function getRadioStationName()
    {
        return $this->_radioStationName;
    }

    public function getRadioStationSlogan()
    {
        return $this->_radioStationSlogan;
    }

    public function getRadioStationLogoUrl()
    {
        return $this->_radioStationLogoUrl;
    }
}
