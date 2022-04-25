<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\Api\AlexaCustomerProfileApi;
use Convo\Core\Adapters\Alexa\Api\AlexaPersonProfileApi;
use Convo\Core\Adapters\Alexa\Api\AlexaRemindersApi;
use Convo\Core\Adapters\Alexa\Api\InsufficientPermissionsGrantedException;
use Convo\Core\Factory\InvalidComponentDataException;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class GetAmazonCustomerProfileElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	private $_name;

	private $_profileType;

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_ok = array();

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onPermissionNotGranted = array();

	/**
	 * @var AlexaCustomerProfileApi
	 */
	private $_alexaCustomerProfileApi;


    /**
     * @var AlexaPersonProfileApi
     */
    private $_alexaPersonProfileApi;


    /**
     * @var AlexaRemindersApi
     */
    private $_alexaRemindersApi;

	/**
	 * @var \Convo\Core\IServiceDataProvider
	 */
	private $_convoServiceDataProvider;

	public function __construct($properties, $alexaCustomerProfileApi, $alexaPersonProfileApi, $alexaRemindersApi, $convoServiceDataProvider)
	{
		parent::__construct($properties);

		$this->_name = $properties['name'] ?? 'customerProfile';
		$this->_profileType = $properties['profile_type'] ?? 'CUSTOMER';

		foreach ($properties['ok'] as $element) {
			$this->_ok[] = $element;
			$this->addChild($element);
		}

		foreach ($properties['on_permission_not_granted'] as $element) {
			$this->_onPermissionNotGranted[] = $element;
			$this->addChild($element);
		}

		$this->_alexaCustomerProfileApi = $alexaCustomerProfileApi;
		$this->_alexaPersonProfileApi = $alexaPersonProfileApi;
		$this->_alexaRemindersApi = $alexaRemindersApi;
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

		$name = $this->evaluateString($this->_name);
		$profileType = $this->evaluateString($this->_profileType);

		if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
			$amazon_platform_config = $this->_convoServiceDataProvider->getServicePlatformConfig(
					new RestSystemUser(), $this->getService()->getId(), IPlatformPublisher::MAPPING_TYPE_DEVELOP
				)['amazon'] ?? [];
			$amazon_skill_permissions = $amazon_platform_config['permissions'] ?? [];

			$shouldGetFullName = in_array('alexa::profile:name:read', $amazon_skill_permissions);
			$shouldGetGivenName = in_array('alexa::profile:given_name:read', $amazon_skill_permissions);
			$shouldGetEmailAddress = in_array('alexa::profile:email:read', $amazon_skill_permissions);
			$shouldGetPhoneNumber = in_array('alexa::profile:mobile_number:read', $amazon_skill_permissions);
			$shouldGetReminders = in_array('alexa::alerts:reminders:skill:readwrite', $amazon_skill_permissions);

			$alexaProfile = [];
            $missingPermissions = [];
            $configuredPermissions = [];
			$this->_logger->info('Getting Amazon ['.$profileType.'] with the following permissions [' . json_encode($amazon_skill_permissions) . ']');
            if ($shouldGetReminders) {
                $configuredPermissions[] = 'reminders';
                try {
                    $alexaProfile['reminders'] = $this->_alexaRemindersApi->getAllReminders($request);
                } catch (InsufficientPermissionsGrantedException $e) {
                    $missingPermissions[] = 'reminders';
                }
            }
            $profileVariableName = '';
            switch ($profileType) {
                case 'CUSTOMER':
                    $profileVariableName = 'customer_profile';
                    if ($shouldGetFullName) {
                        $configuredPermissions[] = 'fullName';
                        try {
                            $alexaProfile['fullName'] = $this->_alexaCustomerProfileApi->getCustomerFullName($request);
                        } catch (InsufficientPermissionsGrantedException $e) {
                            $missingPermissions[] = 'fullName';
                        }
                    }
                    if ($shouldGetGivenName) {
                        $configuredPermissions[] = 'givenName';
                        try {
                            $alexaProfile['givenName'] = $this->_alexaCustomerProfileApi->getCustomerGivenName($request);
                        } catch (InsufficientPermissionsGrantedException $e) {
                            $missingPermissions[] = 'givenName';
                        }
                    }
                    if ($shouldGetEmailAddress) {
                        $configuredPermissions[] = 'emailAddress';
                        try {
                            $alexaProfile['emailAddress'] = $this->_alexaCustomerProfileApi->getCustomerEmailAddress($request);
                        } catch (InsufficientPermissionsGrantedException $e) {
                            $missingPermissions[] = 'emailAddress';
                        }
                    }
                    if ($shouldGetPhoneNumber) {
                        $configuredPermissions[] = 'phoneNumber';
                        try {
                            $alexaProfile['phoneNumber'] = $this->_alexaCustomerProfileApi->getCustomerPhoneNumber($request);
                        } catch (InsufficientPermissionsGrantedException $e) {
                            $missingPermissions[] = 'phoneNumber';
                        }
                    }
                    break;
                case 'PERSON':
                    $profileVariableName = 'person_profile';
                    $alexaProfile['personId'] = $request->getPersonId();

                    if ($shouldGetFullName) {
                        $configuredPermissions[] = 'fullName';
                        try {
                            $alexaProfile['fullName'] = $this->_alexaPersonProfileApi->getPersonFullName($request);
                        } catch (InsufficientPermissionsGrantedException $e) {
                            $missingPermissions[] = 'fullName';
                        }
                    }
                    if ($shouldGetGivenName) {
                        $configuredPermissions[] = 'givenName';
                        try {
                            $alexaProfile['givenName'] = $this->_alexaPersonProfileApi->getPersonGivenName($request);
                        } catch (InsufficientPermissionsGrantedException $e) {
                            $missingPermissions[] = 'givenName';
                        }
                    }
                    if ($shouldGetPhoneNumber) {
                        $configuredPermissions[] = 'phoneNumber';
                        try {
                            $alexaProfile['phoneNumber'] = $this->_alexaPersonProfileApi->getPersonPhoneNumber($request);
                        } catch (InsufficientPermissionsGrantedException $e) {
                            $missingPermissions[] = 'phoneNumber';
                        }
                    }
                    break;
                default:
                    throw new InvalidComponentDataException('['.$profileType. '] is not supported.');
            }

            if (empty($missingPermissions)) {
                $selected_flow = $this->_ok;
                $this->_logger->info('Got all requested data of Amazon Customer Profile [' . json_encode($alexaProfile) . ']');
                $params->setServiceParam($name, [$profileVariableName => $alexaProfile]);
            } else {
                $selected_flow = $this->_onPermissionNotGranted;
                $this->_logger->info('Missing permissions ['.json_encode($missingPermissions).'] of configured permissions ['.json_encode($configuredPermissions).']');
                $this->_logger->info('Could not get all requested data of Amazon Customer Profile [' . json_encode($alexaProfile) . ']');
                $params->setServiceParam($name, [
                    'configured_permissions' => $configuredPermissions,
                    'missing_permissions' => $missingPermissions,
                    $profileVariableName => $alexaProfile
                ]);
            }

            foreach ($selected_flow as $element) {
                $element->read( $request, $response);
            }
		}
	}
}
