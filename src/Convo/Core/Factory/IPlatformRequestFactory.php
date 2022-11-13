<?php


namespace Convo\Core\Factory;


use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IIntentAwareRequest;
use Convo\Core\ConvoServiceInstance;

interface IPlatformRequestFactory
{
    /**
     * @param IConvoRequest $request
     * @param \Convo\Core\IAdminUser $user
     * @param ConvoServiceInstance $service
     * @param string $platformId
     * @return IIntentAwareRequest
     */
    function toIntentRequest( IConvoRequest $request, \Convo\Core\IAdminUser $user, ConvoServiceInstance $service, $platformId);
}
