<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * Defines filtering mechanism used in Convoworks. 
 * 
 * Filters are able to determine are they required to activate (accepts) and if they do, they should return result..
 * @author Tole
 *
 */
interface IRequestFilter extends \Convo\Core\Workflow\IBasicServiceComponent
{


    /**
     * Tests is request acceptable at all - by type.
     * 
     * @param \Convo\Core\Workflow\IConvoRequest $request
     * @return boolean
     */
    public function accepts( \Convo\Core\Workflow\IConvoRequest $request);

    /**
     * Will try to read request. If no match is found, returning result will be empty.
     * 
     * @param \Convo\Core\Workflow\IConvoRequest $request
     * @return \Convo\Core\Workflow\IRequestFilterResult
     */
    public function filter( \Convo\Core\Workflow\IConvoRequest $request);



}