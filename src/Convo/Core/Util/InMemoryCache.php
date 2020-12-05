<?php declare(strict_types=1);

namespace Convo\Core\Util;

use Psr\SimpleCache\CacheInterface;

class InMemoryCache implements CacheInterface
{

	private $_data =   [];

	public function __construct()
	{
	}

	/**
	 * {@inheritDoc}
	 * @see \Psr\SimpleCache\CacheInterface::get()
	 */
	public function get($key, $default = null)
	{
	    if ( $this->has($key)) {
	        return $this->_data[$key];
	    }
	    return $default;
	}

	public function getMultiple($keys, $default = null)
	{
		$ret = [];

		foreach ($keys as $key) {
			$ret[$key] = $this->get( $key, $default);
		}

		return $ret;
	}

	public function set($key, $value, $ttl = null)
	{
	    $this->_data[$key] =   $value;
		return true;
	}

	public function setMultiple($values, $ttl = null)
	{
		$ret = true;

		foreach ($values as $key => $value) {
			$ret = $ret && $this->set($key, $value, $ttl);
		}

		return $ret;
	}

	public function clear()
	{
	    $this->_data   =   [];
		return true;
	}

	public function delete( $key)
	{
	    if ( $this->has( $key)) {
	        unset( $this->_data[$key]);
	        return true;
	    }
	    return false;
	}

	public function deleteMultiple( $keys)
	{
		$ret = true;

		foreach ($keys as $key) {
			$ret = $ret && $this->delete($key);
		}

		return $ret;
	}

	public function has($key)
	{
	    return isset( $this->_data[$key]);
	}

	// UTIL
	public function __toString()
	{
	    return get_class($this) . '[' . count( $this->_data) . ']';
	}
}
