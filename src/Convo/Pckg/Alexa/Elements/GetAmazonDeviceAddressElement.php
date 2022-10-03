<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\Api\AlexaDeviceAddressApi;
use Convo\Core\Adapters\Alexa\Api\InsufficientPermissionsGrantedException;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class GetAmazonDeviceAddressElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_deviceAddressStatusVar;

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_ok = array();

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onPermissionNotGranted = array();

	/**
	 * @var AlexaDeviceAddressApi
	 */
	private $_alexaDeviceAddressApi;

	/**
	 * @var \Convo\Core\IServiceDataProvider
	 */
	private $_convoServiceDataProvider;

	public function __construct($properties, $alexaDeviceAddressApi, $convoServiceDataProvider)
	{
		parent::__construct($properties);

		$this->_deviceAddressStatusVar = $properties['device_address_status_var'] ?? 'deviceAddress';

		foreach ($properties['ok'] as $element) {
			$this->_ok[] = $element;
			$this->addChild($element);
		}

		foreach ($properties['on_permission_not_granted'] as $element) {
			$this->_onPermissionNotGranted[] = $element;
			$this->addChild($element);
		}

		$this->_alexaDeviceAddressApi = $alexaDeviceAddressApi;
		$this->_convoServiceDataProvider = $convoServiceDataProvider;
	}

	/**
	 * @param IConvoRequest $request
	 * @param IConvoResponse $response
	 */
	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		$scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_SESSION;
		$params = $this->getService()->getComponentParams($scope_type, $this);

		$deviceAddressStatusVar = $this->evaluateString($this->_deviceAddressStatusVar);

		if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
			$amazon_platform_config = $this->_convoServiceDataProvider->getServicePlatformConfig(
					new RestSystemUser(), $this->getService()->getId(), IPlatformPublisher::MAPPING_TYPE_DEVELOP
				)['amazon'] ?? [];
			$amazon_skill_permissions = $amazon_platform_config['permissions'] ?? [];

			$shouldGetAddress = in_array('alexa::devices:all:address:full:read', $amazon_skill_permissions);
			$countryAndPostalCode = in_array('alexa:devices:all:address:country_and_postal_code:read', $amazon_skill_permissions);

			$alexaAddress = [];
			$this->_logger->info('Getting Amazon Device Address with the following permissions [' . json_encode($amazon_skill_permissions) . ']');
            $selected_flow = null;
            if ($shouldGetAddress) {
                try {
                    $alexaAddress = $this->_alexaDeviceAddressApi->getAddress($request);
                    $selected_flow = $this->_ok;
                } catch (InsufficientPermissionsGrantedException $e) {
                    $selected_flow = $this->_onPermissionNotGranted;
                }
            }

            if ($countryAndPostalCode) {
                try {
                    $alexaAddress = $this->_alexaDeviceAddressApi->getCountryAndPostalCode($request);
                    $selected_flow = $this->_ok;
                } catch (InsufficientPermissionsGrantedException $e) {
                    $selected_flow = $this->_onPermissionNotGranted;
                }
            }

            $this->_logger->debug('Got device address object ['.print_r($alexaAddress, true).']');
            $params->setServiceParam($deviceAddressStatusVar, $alexaAddress);

            foreach ($selected_flow as $element) {
                $element->read( $request, $response);
            }
		}
	}
}
