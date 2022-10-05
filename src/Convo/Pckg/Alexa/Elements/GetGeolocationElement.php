<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class GetGeolocationElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    private $_acceptableFreshness;

    private $_accuracyThreshold;

    private $_geolocationStatusVar;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_okFlow = array();

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_nokFlow = array();

    public function __construct($properties)
    {
        parent::__construct($properties);
        $this->_acceptableFreshness = $properties['acceptable_freshness'] ?? 60;
        $this->_accuracyThreshold = $properties['accuracy_threshold'] ?? 100;
        $this->_geolocationStatusVar = $properties['geolocation_status_var'] ?? 'status';

        foreach ($properties['ok'] as $element) {
            $this->_okFlow[] = $element;
            $this->addChild($element);
        }

        foreach ($properties['nok'] as $element) {
            $this->_nokFlow[] = $element;
            $this->addChild($element);
        }
    }

    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest'))
        {
            $this->_logger->info('Going to set geolocation variable...');

            $scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST;
            $params = $this->getService()->getServiceParams($scope_type);

            $acceptableFreshness = $this->evaluateString($this->_acceptableFreshness);
            $accuracy_threshold = $this->evaluateString($this->_accuracyThreshold);
            $isGeoLocationSupported = $request->isGeoLocationSupported();
            $isGeoLocationPermissionGranted = $request->isGeoLocationPermissionGranted();
            $isLocationSharingEnabled = $request->isLocationSharingEnabled();
            $geolocation = $request->getGeolocation();
            $exceedsAccuracyThreshold = $this->_exceedsAccuracyThreshold($accuracy_threshold, $geolocation);
            $isDataFreshEnough = $this->_isDataFreshEnough($request, $geolocation, $acceptableFreshness);
            $isGeolocationAvailable = !empty($geolocation);


            $geolocationStatusVar = [
                'is_geolocation_supported' => $isGeoLocationSupported,
                'is_geolocation_permission_granted' => $isGeoLocationPermissionGranted,
                'is_location_sharing_enabled' => $isLocationSharingEnabled,
                'exceeds_accuracy_threshold' => $exceedsAccuracyThreshold,
                'is_data_fresh_enough' => $isDataFreshEnough,
                'is_geolocation_available' => $isGeolocationAvailable,
                'geolocation' => $geolocation
            ];

            $this->_logger->debug('Printing geolocation status var ['.print_r($geolocationStatusVar, true).']');

            $selectedFlow = $this->_okFlow;

            if (!$isGeoLocationSupported || !$isGeoLocationPermissionGranted || $exceedsAccuracyThreshold
                || !$isDataFreshEnough || !$isGeolocationAvailable || !$isLocationSharingEnabled) {
                $selectedFlow = $this->_nokFlow;
            }

            $params->setServiceParam($this->_geolocationStatusVar, $geolocationStatusVar);

            foreach ($selectedFlow as $element) {
                $element->read($request, $response);
            }
        }
    }

    private function _exceedsAccuracyThreshold($accuracyThreshold, $geolocation) {
        $exceedsAccuracyThreshold = false;
        if (isset($geolocation['coordinate']['accuracyInMeters']) && $accuracyThreshold <= $geolocation['coordinate']['accuracyInMeters']) {
            $exceedsAccuracyThreshold = true;
        }
        return $exceedsAccuracyThreshold;
    }

    private function _isDataFreshEnough($request, $geolocation, $acceptableFreshness) {
        $isDataFreshEnough = false;

        if (isset($geolocation['timestamp'])) {
            $requestTimestamp = strtotime($request->getPlatformData()['request']['timestamp']);
            $geolocationTimestamp = strtotime($geolocation['timestamp']);

            $this->_logger->debug('Got request timestamp ['.$requestTimestamp.']');
            $this->_logger->debug('Got geolocation timestamp ['.$geolocationTimestamp.']');

            $currentFreshness = ($requestTimestamp - $geolocationTimestamp);

            $this->_logger->debug('Got current freshness ['.$currentFreshness.']');
            if ($currentFreshness <= $acceptableFreshness) {
                $isDataFreshEnough = true;
            }
        }

        return $isDataFreshEnough;
    }
}
