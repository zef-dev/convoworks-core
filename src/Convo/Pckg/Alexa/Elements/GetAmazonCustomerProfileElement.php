<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\Api\AlexaCustomerProfileApi;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class GetAmazonCustomerProfileElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_name;

	private $_shouldGetFullName;
	private $_shouldGetGivenName;
	private $_shouldGetEmailAddress;
	private $_shouldGetPhoneNumber;

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_ok = array();

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onPermissionNotGranted = array();

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_nok = array();

	/**
	 * @var AlexaCustomerProfileApi
	 */
	private $_alexaCustomerProfileApi;

	public function __construct($properties, $alexaCustomerProfileApi)
	{
		parent::__construct($properties);

		$this->_name = $properties['name'] ?? 'customerProfile';
		$this->_shouldGetFullName = $properties['should_get_full_name'] ?? false;
		$this->_shouldGetGivenName = $properties['should_get_given_name'] ?? false;
		$this->_shouldGetEmailAddress = $properties['should_get_email_address'] ?? false;
		$this->_shouldGetPhoneNumber = $properties['should_get_phone_number'] ?? false;

		foreach ($properties['ok'] as $element) {
			$this->_ok[] = $element;
			$this->addChild($element);
		}

		foreach ($properties['on_permission_not_granted'] as $element) {
			$this->_onPermissionNotGranted[] = $element;
			$this->addChild($element);
		}

		foreach ($properties['nok'] as $element) {
			$this->_nok[] = $element;
			$this->addChild($element);
		}

		$this->_alexaCustomerProfileApi = $alexaCustomerProfileApi;
	}

	/**
	 * @param IConvoRequest $request
	 * @param IConvoResponse $response
	 */
	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		$scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_SESSION;
		$params = $this->getService()->getComponentParams($scope_type, $this);

		$name = $this->evaluateString($this->_name);

		$shouldGetFullName = $this->evaluateString($this->_shouldGetFullName);
		$shouldGetGivenName = $this->evaluateString($this->_shouldGetGivenName);
		$shouldGetEmailAddress = $this->evaluateString($this->_shouldGetEmailAddress);
		$shouldGetPhoneNumber = $this->evaluateString($this->_shouldGetPhoneNumber);

		if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
			$customerProfile = [];

			try {
				if ($shouldGetFullName) {
					$customerProfile['fullName'] = $this->_alexaCustomerProfileApi->getCustomerFullName($request);
				}

				if ($shouldGetGivenName) {
					$customerProfile['givenName'] = $this->_alexaCustomerProfileApi->getCustomerGivenName($request);
				}

				if ($shouldGetEmailAddress) {
					$customerProfile['emailAddress'] = $this->_alexaCustomerProfileApi->getCustomerEmailAddress($request);
				}

				if ($shouldGetPhoneNumber) {
					$customerProfile['phoneNumber'] = $this->_alexaCustomerProfileApi->getCustomerPhoneNumber($request);
				}

				$params->setServiceParam($name, $customerProfile);

				if ( !empty( $this->_ok)) {
					foreach ( $this->_ok as $element) {
						$element->read( $request, $response);
					}
				}
			} catch (\Exception $e) {
				$params->setServiceParam($name, ['error' => $e->getMessage()]);

				if ($e->getCode() === 403) {
					if ( !empty( $this->_onPermissionNotGranted)) {
						foreach ($this->_onPermissionNotGranted as $element) {
							$element->read( $request, $response);
						}
					}
				} else {
					if ( !empty( $this->_nok)) {
						foreach ( $this->_nok as $element) {
							$element->read( $request, $response);
						}
					}
				}
			}
		}
	}
}