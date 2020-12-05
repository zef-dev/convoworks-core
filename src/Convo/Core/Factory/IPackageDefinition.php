<?php declare(strict_types=1);

namespace Convo\Core\Factory;

/**
 * @author Tole
 *
 * Packages provides component definitions and are creating own components.
 */
interface IPackageDefinition
{
	/**
	 * Returns namespace name.
	 *
	 * @return string
	 */
	public function getNamespace();


	/**
	 * @return array
	 */
	public function getRow();
}
