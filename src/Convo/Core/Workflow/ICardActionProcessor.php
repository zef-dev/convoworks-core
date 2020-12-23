<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * Workflow processors are able to filter request (usually delegating to child request filters) and process it.
 * @author Tole
 *
 */
interface ICardActionProcessor extends \Convo\Core\Workflow\IConversationProcessor
{
    /**
     * @return string
     */
    public function getActionID();
}
