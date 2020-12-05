<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

interface ICatalogSource
{
	/**
	 * Get a list of catalogue values as an array of strings
	 * @param string $platform Vendor ID, either 'amazon' or 'dialogflow'
	 * @return array
	 */
	public function getCatalogValues($platform);

	/**
	 * Get the current version of a catalog. This is currently used with Amazon so they decide
	 * whether to use cached catalog values, or to fetch new ones.
	 * @return string
	 */
	public function getCatalogVersion();
}
