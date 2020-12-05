<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * Fragments are named components - so you can find them by name.
 * @author Tole
 * @todo check relation with IIdentifiableComponent
 */
interface IFragmentComponent extends IBasicServiceComponent
{

	/**
	 * Returns fragment name
	 * @return string
	 */
	public function getName();

    /**
     * Returns fragment name as shown in editor
     * @return string
     */
	public function getWorkflowName();
}
