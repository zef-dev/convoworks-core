<?php declare(strict_types=1);

namespace Convo\Core\Params;

/**
 * @author Tole
 * Service params are defining unified way to store and access data in a simple key=>val manner.
 * Important ability of the service params is that they can return all stored valueas as associative array
 */
interface IServiceParams
{
	/**
	 * Returns value stored with given name or null if not exist. Returned value might be anything you stored in, null, string, numeric, array, object.
	 * @param string $name
	 * @return mixed
	 */
	public function getServiceParam( $name);

	/**
	 * Stires value under a given name.
	 * @param string $name
	 * @param mixed $value
	 */
	public function setServiceParam( $name, $value);
	
	/**
	 * Returns all stored data as assoc array - parameter names are used as array keys.
	 * @return array
	 */
	public function getData();
}