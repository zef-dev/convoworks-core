<?php declare(strict_types=1);

namespace Convo\Core\Params;

/**
 * @author Tole
 * 
 * This interface provides unified way to access various types of service params (scope, level, key).
 * Use this accessors to generate unique keys when accessing storage.
 */
interface IServiceParamsScope
{
	const SCOPE_TYPE_SESSION		=	'session';
	const SCOPE_TYPE_INSTALLATION	=	'installation';
	const SCOPE_TYPE_REQUEST		=	'request';
	const SCOPE_TYPE_USER			=	'user';

	const LEVEL_TYPE_SERVICE		=	'service';
// 	const LEVEL_TYPE_BLOCK			=	'block';
	const LEVEL_TYPE_COMPONENT		=	'component';
	
	/**
	 * @return string
	 */
	public function getServiceId();

	/**
	 * @return string
	 */
	public function getScopeType();
	
	/**
	 * @return string
	 */
	public function getLevelType();
	
	
	/**
	 * @return string
	 */
	public function getKey();
	
	

}