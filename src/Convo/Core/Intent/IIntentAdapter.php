<?php declare(strict_types=1);

namespace Convo\Core\Intent;

interface IIntentAdapter extends \Convo\Core\Workflow\IServiceWorkflowComponent
{
    /**
     * @param \Convo\Core\Workflow\IIntentAwareRequest $request
     * @return \Convo\Core\Workflow\IRequestFilterResult
     */
    public function read( \Convo\Core\Workflow\IIntentAwareRequest $request);
    
    /**
     * @param string $platformId
     * @return string 
     */
    public function getPlatformIntentName( $platformId);
 
}