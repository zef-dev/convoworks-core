<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Api;

interface IGoogleNlpFactory
{
	/**
	 * @param string $apiKey
	 * @return \Convo\Pckg\Gnlp\Api\IGoogleNlpApi
	 */
	public function getApi( $apiKey);
}