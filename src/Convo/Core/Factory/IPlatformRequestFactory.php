<?php


namespace Convo\Core\Factory;


use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IIntentAwareRequest;

interface IPlatformRequestFactory
{
    /**
     * @param IConvoRequest $request
     * @param \Convo\Core\IAdminUser $user
     * @param $serviceId
     * @param $platformId
     * @param $variant
     * @return IIntentAwareRequest
     */
    function toIntentRequest( IConvoRequest $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId, $variant = '');
}
