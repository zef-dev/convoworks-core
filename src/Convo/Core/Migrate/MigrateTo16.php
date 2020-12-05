<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo16 extends AbstractMigration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getVersion()
	{
		return 16;
	}
	
	public function migrateConfig( $config)
	{
	    $PLATFORMS =   [ 'amazon', 'dialogflow', 'convo_chat', 'google_actions'];
	    
	    foreach ( $PLATFORMS as $platform_id) {
	        if ( isset( $config[$platform_id]['enabled']) && !$config[$platform_id]['enabled']) {
	            unset( $config[$platform_id]);
	            continue;
	        }
	        if ( isset( $config[$platform_id]['enabled'])) {
	            unset( $config[$platform_id]['enabled']);
	        }
	    }
	    
	    return $config;
	}
	
	public function migrateMeta( $meta)
	{
	    $PLATFORMS =   [ 'amazon', 'dialogflow', 'convo_chat', 'google_actions'];
	    
	    if ( isset( $meta['release_mapping']) && !empty( $meta['release_mapping'])) {
	        unset( $meta['versions']);
	        return $meta;
	    }
	    
	    foreach ( $PLATFORMS as $platform_id) {
	        
	        if ( !isset( $meta['versions'][$platform_id])) {
	           continue;    
	        }
	        
	        $meta['release_mapping'][$platform_id]['a']    =   [
	            'type' => 'develop'
	        ];
	    }
	    
	    unset( $meta['versions']);
	    
	    return $meta;
	}
}
