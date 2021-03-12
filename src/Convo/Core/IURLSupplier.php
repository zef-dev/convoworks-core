<?php


namespace Convo\Core;


interface IURLSupplier
{
    const SYSTEM_URL_FOR_AMAZON_ALLOWED_RETURN_URL = 'allowed_return_url_for_amazon';
    const SERVICE_URL_FOR_PRIVACY_POLICY = 'privacy_policy';
    const SERVICE_URL_FOR_TERMS_OF_USE = 'terms_of_use';
    const SERVICE_URL_FOR_ACCOUNT_LINKING = 'account_linking';
    /**
     * @param $forWhat
     * throw DataItemNotFoundException
     * @return array
     */
    public function getSystemUrl($forWhat);

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
    public function getServiceUrl($serviceId, $platformId, $forWhat, $accountLinkingMode = '');
}
