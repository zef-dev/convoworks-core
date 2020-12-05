<?php declare(strict_types=1);

namespace Convo\Core\Migrate;


class MigrateTo3 extends AbstractMigration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getVersion()
	{
		return 3;
	}
	
	protected function _migrateService( $serviceData)
	{
		$serviceData	=	parent::_migrateService( $serviceData);

		$found			=	false;
		foreach ( $serviceData['blocks'] as $block_data) {
			if ( $block_data['properties']['block_id'] === \Convo\Core\ConvoServiceInstance::BLOCK_TYPE_MEDIA_CONTROLS) {
				$found	=	true;
			}
		}
		
		if ( !$found) {
			$serviceData['blocks'][]	=	[
					'class' => '\\Convo\\Pckg\\Core\\Elements\\ConversationBlock',
					'properties' => [
							'block_id' => \Convo\Core\ConvoServiceInstance::BLOCK_TYPE_MEDIA_CONTROLS,
							'elements' => [],
							'processors' => []
					]
			];
		}
		
		return $serviceData;
	}
	
	
}