<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

interface IServiceCatalog
{
    /**
	 * Get a list of catalogue values as an array of strings
	 * @param string $platform Vendor ID, either 'amazon' or 'dialogflow'
	 * @return array
	 */
	public function getCatalogValues($platform);
}