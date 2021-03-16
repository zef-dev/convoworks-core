<?php


namespace Convo\Core;


interface IURLSupplier
{
    /**
     * Generates all system relevant URLs.
     * @return array
     */
    public function getSystemUrls();

    /**
     * Generates URLs based on serviceId.
     * Also includes the generation of account linking urls.
     * @param $serviceId
     * @return array
     */
    public function getServiceUrls($serviceId);
}
