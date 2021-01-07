<?php


namespace Convo\Pckg\Core\Processors;


use Convo\Core\Workflow\IConvoCardRequest;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IRequestFilterResult;

class CardActionProcessor extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\ICardActionProcessor
{

    /**
     * @return string
     */
    public function getActionID()
    {
        // TODO: Implement getActionName() method.
        return '';
    }

    /**
     * @inheritDoc
     */
    public function process(IConvoRequest $request, IConvoResponse $response, IRequestFilterResult $result)
    {
        // TODO: Implement process() method.
    }

    /**
     * @inheritDoc
     */
    public function filter(IConvoRequest $request)
    {
        // TODO: Implement filter() method.
    }
}
