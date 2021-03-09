<?php


namespace Convo\Core;


interface IURLSupplier
{
    /**
     * @param $forWhat
     * @return string
     */
    public function getStaticUrl($forWhat);

    /**
     * @param $serviceId
     * @param $platformId
     * @param $forWhat
     * @return string
     */
    public function getDynamicUrl($serviceId, $platformId, $forWhat);

    /**
     * @param $serviceId
     * @param $platformID
     * @param $accountLinkingMode
     * @throw DataItemNotFoundException
     * @return array
     */
    public function getAccountLinkingURLs($serviceId, $platformID, $accountLinkingMode);
}
