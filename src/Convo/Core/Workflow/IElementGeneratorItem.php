<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

interface IElementGeneratorItem
{
    /**
     * @return IConversationElement
     */
    public function getElement();
}
