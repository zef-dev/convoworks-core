<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

interface ISpecialRoleRequest extends IConvoRequest
{

	/**
	 *
	 * @return string
	 */
	public function getSpecialRole();

}
