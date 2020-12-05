<?php


namespace Convo\Core\Factory;


use Convo\Core\Workflow\IConvoRequest;

interface IPlatformRequestFactory
{
    function toIntentRequest( IConvoRequest $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId);
}