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

	public function __construct($logger, $httpFactory)
	{
		parent::__construct($logger, $httpFactory);
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

    /**
     * @param AmazonCommandRequest $request
     * @throws AlexaApiException
     * @return string
     */
    public function getDistanceMeasurementUnit( AmazonCommandRequest $request)
    {
        try {
            $str_distance_measurement_unit  =   $this->_executeAlexaApiRequest(
                $request,
                IHttpFactory::METHOD_GET,
                '/v2/devices/'.$request->getDeviceId().'/settings/'.self::ALEXA_SYSTEM_DISTANCE_UNITS);
            $this->_logger->info( 'Got distance measurement unit ['.$str_distance_measurement_unit.'] for device ['.$request->getDeviceId().']['.$request->getServiceId().']');
            return $str_distance_measurement_unit;
        } catch ( ClientExceptionInterface $e) {
            throw new AlexaApiException( 'Failed to get distance measurement unit for the request ['.$request.']', null, $e);
        }
    }

    /**
     * @param AmazonCommandRequest $request
     * @throws AlexaApiException
     * @return string
     */
    public function getTemperatureMeasurementUnit( AmazonCommandRequest $request)
    {
        try {
            $str_temperature_measurement_unit  =   $this->_executeAlexaApiRequest(
                $request,
                IHttpFactory::METHOD_GET,
                '/v2/devices/'.$request->getDeviceId().'/settings/'.self::ALEXA_SYSTEM_TEMPERATURE_UNIT);
            $this->_logger->info( 'Got temperature measurement unit ['.$str_temperature_measurement_unit.'] for device ['.$request->getDeviceId().']['.$request->getServiceId().']');
            return $str_temperature_measurement_unit;
        } catch ( ClientExceptionInterface $e) {
            throw new AlexaApiException( 'Failed to get temperature measurement unit for the request ['.$request.']', null, $e);
        }
    }
}
