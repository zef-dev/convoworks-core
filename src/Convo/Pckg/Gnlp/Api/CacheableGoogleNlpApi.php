<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Api;

class CacheableGoogleNlpApi implements \Convo\Pckg\Gnlp\Api\IGoogleNlpApi
{
	/**
	 * @var \Convo\Pckg\Gnlp\Api\IGoogleNlpApi
	 */
	private $_nlpApi;

	/**
	 * @var \Psr\SimpleCache\CacheInterface
	 */
	private $_cache;

	public function __construct($cache, $nlpApi)
	{
    	$this->_cache  			=   $cache;
        $this->_nlpApi  		=   $nlpApi;
	}

	public function analyzeTextSyntax($text)
	{
		$key = $this->_getKey('analyzeTextSyntax', $text);

		if ($this->_cache->has($key)) {
			$data = $this->_cache->get($key);
		} else {
			$data = $this->_nlpApi->analyzeTextSyntax($text);
			$this->_cache->set($key, $data);
			$this->_cache->set($key . '_EXTRA', ['raw' => $text]);
		}

		return $data;
	}

	public function analyzeTextSentiment($text)
	{
		$key = $this->_getKey('analyzeTextSentiment', $text);

		if ($this->_cache->has($key)) {
			$data = $this->_cache->get($key);
		} else {
			$data = $this->_nlpApi->analyzeTextSentiment($text);
			$this->_cache->set($key, $data);
			$this->_cache->set($key . '_EXTRA', ['raw' => $text]);
		}

		return $data;
	}

	public function analyzeTextEntities($text)
	{
		$key = $this->_getKey('analyzeTextEntities', $text);

		if ($this->_cache->has($key)) {
			$data = $this->_cache->get($key);
		} else {
			$data = $this->_nlpApi->analyzeTextEntities($text);
			$this->_cache->set($key, $data);
			$this->_cache->set($key . '_EXTRA', ['raw' => $text]);
		}

		return $data;
	}

	private function _getKey($namespace, $text)
	{
		return $namespace . '_' . md5($text);
	}

	// UTIL
	public function __toString()
	{
		return get_class($this);
	}
}
