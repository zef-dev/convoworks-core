<?php declare(strict_types=1);

namespace Convo\Core\Migrate;


class MigrateTo1 extends AbstractMigration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getVersion()
	{
		return 1;
	}
	
	protected function _migrateService( $serviceData) 
	{
		$serviceData	=	parent::_migrateService( $serviceData);
		
		if ( !isset( $serviceData['subroutines'])) {
			$this->_logger->debug( 'Creating subroutines array');
			$serviceData['subroutines']	=	[];
		}
		
		foreach ( $serviceData['blocks'] as $i=>$block_data) 
		{
			if ( strpos( $block_data['properties']['block_id'], '_read_') === 0) 
			{
				if ( !empty( $block_data['properties']['elements'])) {
					$this->_logger->debug( 'Migrating block ['.$block_data['properties']['block_id'].'] to read subroutine');
					$serviceData['subroutines'][]	=	[
							'class' => '\\Adm\\Alexax\\Elem\\ElementsSubroutine',
							'namespace' => 'convo-core',
							'properties' => [
									'subroutine_id'	=> $block_data['properties']['block_id'],
									'name'	=> $block_data['properties']['block_id'],
									'description'	=> '',
									'elements'	=> $block_data['properties']['elements'],
									'_component_id'	=> $block_data['properties']['_component_id'],
									'_workflow'	=> 'read',
							]
					];
				}
				
				if ( !empty( $block_data['properties']['processors'])) {
					$this->_logger->debug( 'Migrating block ['.$block_data['properties']['block_id'].'] to process subroutine');
					$serviceData['subroutines'][]	=	[
							'class' => '\\Adm\\Alexax\\Proc\\ProcessorSubroutine',
							'namespace' => 'convo-core',
							'properties' => [
									'subroutine_id'	=> $block_data['properties']['block_id'],
									'name'	=> $block_data['properties']['block_id'],
									'description'	=> '',
									'processor'	=> $block_data['properties']['processors'][0],
									'_component_id'	=> $block_data['properties']['_component_id'],
									'_workflow'	=> 'process',
							]
					];
				}
				
				unset( $serviceData['blocks'][$i]);
			}
		}
		
		$serviceData['blocks']	=	array_values( $serviceData['blocks']);
		
		return $serviceData;
	}
	
	protected function _migrateComponent( $componentData) {
// 		$this->_logger->debug( 'Checking component ['.$componentData['class'].']');
		// FIX MISSING NAMESPACE
		if ( !isset( $componentData['namespace'])) {
			$this->_logger->debug( 'Fixing missing namespace');
			if ( $componentData['class'] && strpos( $componentData['class'], 'Adm\Nlp') !== false) {
				$componentData['namespace']	=	'google-nlp';
			} elseif ( $componentData['class'] && strpos( $componentData['class'], 'Amz') !== false) {
				$componentData['namespace']	=	'amazon';
			} else {
				$componentData['namespace']	=	'convo-core';
			}
		}
		
		// rename ReadBlockElement to ReadElementsSubroutine
		// rename block_id into subroutine_id
		if ( $componentData['class'] === '\\Adm\\Alexax\\Elem\\ReadBlockElement') {
			$this->_logger->debug( 'Renaming to [\\Adm\\Alexax\\Elem\\ReadElementsSubroutine]');
			$componentData['class']	=	'\\Adm\\Alexax\\Elem\\ReadElementsSubroutine';
			$componentData['properties']['subroutine_id']	=	$componentData['properties']['block_id'];
			unset( $componentData['properties']['block_id']);
		}
		
		// rename VirtualProcessor to ProcessProcessorSubroutine
		// rename block_id into subroutine_id
		if ( $componentData['class'] === '\\Adm\\Alexax\\Proc\\VirtualProcessor') {
			$this->_logger->debug( 'Renaming to [\\Adm\\Alexax\\Proc\\ProcessProcessorSubroutine]');
			$componentData['class']	=	'\\Adm\\Alexax\\Proc\\ProcessProcessorSubroutine';
			$componentData['properties']['subroutine_id']	=	$componentData['properties']['block_id'];
			unset( $componentData['properties']['block_id']);
		}
		
		// NO MORE PROCESSOR_ID
		if ( class_exists( $componentData['class'])) {
			$interfaces = class_implements( $componentData['class']);
			if ( isset( $interfaces['\\Adm\\Alexax\\Proc\\IConversationProcessor'])) {
				$this->_logger->debug( 'Removing processor_id property');
				unset( $componentData['properties']['processor_id']);
			}
		}
		
		return $componentData;
	}

}