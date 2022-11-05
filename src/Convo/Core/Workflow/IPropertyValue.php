<?php

namespace Convo\Core\Workflow;

/**
 * @author Tole
 * Enables encapsulated property value evaluation.
 */
interface IPropertyValue
{
    /**
     * Returns evaluated value
     * @param array $context optional, additional evaluation context
     * @return mixed
     */
    public function getValue( $context=[]);
}
