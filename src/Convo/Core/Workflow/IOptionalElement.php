<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * @author Tole
 * This elements are capable of disabling themself and not even been accountable for the execution
 */
interface IOptionalElement
{
    /**
     * @return bool
     */
    public function isEnabled();
}
