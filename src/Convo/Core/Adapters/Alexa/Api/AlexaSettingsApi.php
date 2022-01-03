<?php

namespace Convo\Core\Adapters\Alexa\Api;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Util\IHttpFactory;
use Psr\Http\Client\ClientExceptionInterface;

class AlexaSettingsApi extends AlexaApi
{

	const ALEXA_SYSTEM_TIMEZONE = 'System.timeZone';
	const ALEXA_SYSTEM_DISTANCE_UNITS = 'System.distanceUnits';
	const ALEXA_SYSTEM_TEMPERATURE_UNIT = 'System.temperatureUnit';

	public function __construct($logger, $webApiCaller)
	{
		parent::__construct($logger, $webApiCaller);
	}

	/**
	 * @todo Split into separate methods
	 * @deprecated
	 * @param AmazonCommandRequest $request request from Alexa
	 * @param string $setting supported setting values System.timeZone|System.distanceUnits|System.temperatureUnit
	 * @return mixed
	 * @throws AlexaApiException
	 */
	public function getSetting(AmazonCommandRequest $request, string $setting) {
		$deviceId = $request->getDeviceId();
		switch ($setting) {
			case self::ALEXA_SYSTEM_TIMEZONE:
			case self::ALEXA_SYSTEM_DISTANCE_UNITS:
			case self::ALEXA_SYSTEM_TEMPERATURE_UNIT:
				try {
					return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, `/v2/devices/${deviceId}/settings/${$setting}`);
				} catch (ClientExceptionInterface $e) {
					throw new AlexaApiException($e->getMessage(), $e->getCode());
				}
			default:
				throw new AlexaApiException('Unsupported Alexa setting [' . $setting . ']');
		}
	}
	
	/**
	 * @param AmazonCommandRequest $request
	 * @throws AlexaApiException
	 * @return \DateTimeZone
	 */
	public function getTimezone( AmazonCommandRequest $request)
	{
	    try {
	        $str_timezone  =   $this->_executeAlexaApiRequest( 
	            $request, 
	            IHttpFactory::METHOD_GET, 
	            '/v2/devices/'.$request->getDeviceId().'/settings/'.self::ALEXA_SYSTEM_TIMEZONE);
	        $this->_logger->info( 'Got timezone ['.$str_timezone.'] for device ['.$request->getDeviceId().']['.$request->getServiceId().']');
	        return new \DateTimeZone( $str_timezone);
	    } catch ( ClientExceptionInterface $e) {
	        throw new AlexaApiException( 'Failed to get timezone for the request ['.$request.']', null, $e);
	    }
	}
}