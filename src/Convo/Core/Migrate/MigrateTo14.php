<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo14 extends AbstractMigration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getVersion()
	{
		return 14;
	}

	protected function _migrateService($serviceData)
	{
		$serviceData = parent::_migrateService($serviceData);

		if (isset($serviceData['configurations'])) {
            foreach ($serviceData['configurations'] as $index => $configuration)
            {
                $this->_logger->debug('Currently handling configuration ['.print_r($configuration, true).']');
                if ($configuration['class'] === "\\Convo\\Core\\Adapters\\Alexa\\AmazonConfiguration") {
                    array_splice($serviceData['configurations'], $index, 1);
                }
            }
        }

		return $serviceData;
	}
}
