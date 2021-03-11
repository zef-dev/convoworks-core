<?php


namespace Convo\Core;


interface IURLSupplier
{
    const AMAZON_LWA_SECURITY_PROFILE_URL = 'https://developer.amazon.com/loginwithamazon/console/site/lwa/overview.html';
    const AMAZON_VENDOR_ID_URL = 'https://developer.amazon.com/settings/console/mycid';

    /**
     * @param $forWhat
     * throw DataItemNotFoundException
     * @return array
     */
    public function getStaticUrl($forWhat);

    /**
     * Used to generate urs based on serviceId, $platformId and purpose.
     * Also includes the generation of account linking urls.
     * @param $serviceId
     * @param $platformId
     * @param $forWhat
     * @param $accountLinkingMode
     * throw DataItemNotFoundException
     * @return array
     */
    public function getDynamicUrl($serviceId, $platformId, $forWhat, $accountLinkingMode = '');
}
