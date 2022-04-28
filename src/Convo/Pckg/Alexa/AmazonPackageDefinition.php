<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa;

use Convo\Core\Adapters\Alexa\Api\AlexaCustomerProfileApi;
use Convo\Core\Adapters\Alexa\Api\AlexaPersonProfileApi;
use Convo\Core\Adapters\Alexa\Api\AlexaRemindersApi;
use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Core\Factory\ComponentDefinition;
use Convo\Core\Factory\IComponentFactory;
use Convo\Core\Workflow\IRunnableBlock;
use Convo\Pckg\Alexa\Workflow\IAplCommandElement;

class AmazonPackageDefinition extends AbstractPackageDefinition
{
	const NAMESPACE	=	'convo-alexa';

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

	/**
	 * @var \Convo\Core\Adapters\Alexa\Api\AmazonUserApi
	 */
	private $_amazonUserApi;

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

	public function __construct(
	    \Psr\Log\LoggerInterface $logger,
        \Convo\Core\Util\IHttpFactory $httpFactory,
        \Convo\Core\IServiceDataProvider $convoServiceDataProvider,
        \Convo\Core\Adapters\Alexa\Api\AmazonUserApi $amazonUserApi,
		AlexaCustomerProfileApi $alexaCustomerProfileApi,
		AlexaPersonProfileApi $alexaPersonProfileApi,
		AlexaRemindersApi $alexaRemindersApi
    ) {
        $this->_httpFactory = $httpFactory;
        $this->_convoServiceDataProvider = $convoServiceDataProvider;
		$this->_amazonUserApi = $amazonUserApi;
		$this->_alexaCustomerProfileApi = $alexaCustomerProfileApi;
		$this->_alexaPersonProfileApi = $alexaPersonProfileApi;
		$this->_alexaRemindersApi = $alexaRemindersApi;

		parent::__construct( $logger, self::NAMESPACE, __DIR__);
	}

	protected function _initIntents()
	{
		return $this->_loadIntents( __DIR__ .'/system-intents.json');
	}

	protected function _initDefintions()
    {
        return [
            new ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\GetAmazonUserElement',
                'Init Amazon user',
                'Initialize an Amazon user.',
                [
                    'name' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'user',
                        'name' => 'Name',
                        'description' => 'Name under which to store the loaded user object in the context',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            'Load Amazon User and set it as <span class="statement"><b>{{ component.properties.name }}</b></span>' .
                            '</div>'
                    ],
                    '_workflow' => 'read',
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'get-amazon-user-element.html'
                    ),
                    '_factory' => new class ($this->_amazonUserApi, $this->_convoServiceDataProvider) implements IComponentFactory
                    {
                        private $_amazonUserApi;
                        private $_convoServiceDataProvider;

                        public function __construct($amazonUserApi, $convoServiceDataProvider)
                        {
                            $this->_amazonUserApi = $amazonUserApi;
                            $this->_convoServiceDataProvider = $convoServiceDataProvider;
                        }

                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Alexa\Elements\GetAmazonUserElement($properties, $this->_amazonUserApi, $this->_convoServiceDataProvider);
                        }
                    }
                ]
            ),
			new ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\GetAmazonCustomerProfileElement',
				'Get Amazon Customer Profile Element',
				'Initialize an Amazon user.',
				[
					'name' => [
						'editor_type' => 'text',
						'editor_properties' => [],
						'defaultValue' => 'status',
						'name' => 'Name',
						'description' => 'Name under which to store the loaded user object in the context',
						'valueType' => 'string'
					],
                    'profile_type' => [
                        'editor_type' => 'select',
                        'editor_properties' => [
                            'options' => ['CUSTOMER' => 'Customer', 'PERSON' => 'Person']
                        ],
                        'defaultValue' => 'CUSTOMER',
                        'name' => 'Profile Type',
                        'description' => 'Selection between the Person Profile API and the Customer Profile API.',
                        'valueType' => 'string'
                    ],
					'ok' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'name' => 'OK',
						'description' => 'Executed if the Amazon Customer Profile could be fetched successfully.',
						'valueType' => 'class'
					],
					'on_permission_not_granted' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'name' => 'On Permission Not Granted',
						'description' => 'Executed if one of the requested permissions are missing.',
						'valueType' => 'class'
					],
					'_preview_angular' => [
						'type' => 'html',
						'template' => '<div class="code">' .
							'Load Amazon User and set it as <span class="statement"><b>{{ component.properties.name }}</b></span>' .
							'</div>'
					],
					'_workflow' => 'read',
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'get-amazon-customer-profile-element.html'
					),
					'_factory' => new class (
                        $this->_alexaCustomerProfileApi,
                        $this->_alexaPersonProfileApi,
                        $this->_alexaRemindersApi,
                        $this->_convoServiceDataProvider
                    ) implements IComponentFactory
					{
						private $_alexaCustomerProfileApi;
						private $_alexaPersonProfileApi;
						private $_alexaRemindersProfileApi;
						private $_convoServiceDataProvider;

						public function __construct($alexaCustomerProfileApi, $alexaPersonProfileApi, $alexaRemindersProfileApi, $convoServiceDataProvider)
						{
							$this->_alexaCustomerProfileApi = $alexaCustomerProfileApi;
							$this->_alexaPersonProfileApi = $alexaPersonProfileApi;
							$this->_alexaRemindersProfileApi = $alexaRemindersProfileApi;
							$this->_convoServiceDataProvider = $convoServiceDataProvider;
						}

						public function createComponent($properties, $service)
						{
							return new \Convo\Pckg\Alexa\Elements\GetAmazonCustomerProfileElement(
                                $properties,
                                $this->_alexaCustomerProfileApi,
                                $this->_alexaPersonProfileApi,
                                $this->_alexaRemindersProfileApi,
                                $this->_convoServiceDataProvider
                            );
						}
					}
				]
			),
            new ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\CreateAlexaReminderElement',
                'Create Alexa Reminder Element',
                'Creates an reminder in Alexa skill.',
                [
                    'is_recurring' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Is recurring?',
                        'description' => 'The reminder can be recurring.',
                        'valueType' => 'boolean'
                    ],
                    'reminder_recurrence_start_date' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence Start Date',
                        'description' => 'Start date of the reminder recurrence.',
                        'valueType' => 'string'
                    ],
                    'reminder_recurrence_start_time' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence Start Time',
                        'description' => 'Start time of the reminder recurrence.',
                        'valueType' => 'string'
                    ],
                    'reminder_recurrence_end_date' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence End Date',
                        'description' => 'End date of the reminder recurrence.',
                        'valueType' => 'string'
                    ],
                    'reminder_recurrence_end_time' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence End Time',
                        'description' => 'End time of the reminder recurrence.',
                        'valueType' => 'string'
                    ],
                    'reminder_recurrence_rules' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence Rules',
                        'description' => 'Expressions which evaluates the recurrence rules of an reminder in RRULES format.',
                        'valueType' => 'string'
                    ],
                    'reminder_type' => [
                        'editor_type' => 'select',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === false",
                            'options' => ['SCHEDULED_ABSOLUTE' => 'Absolute', 'SCHEDULED_RELATIVE' => 'Relative']
                        ],
                        'defaultValue' => 'SCHEDULED_ABSOLUTE',
                        'name' => 'Reminder Type',
                        'description' => 'Type of the reminder which can be Absolute or Relative.',
                        'valueType' => 'string'
                    ],
                    'reminder_schedule_date' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.reminder_type === 'SCHEDULED_ABSOLUTE' && component.properties.is_recurring === false",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Date',
                        'description' => 'Date of the reminder.',
                        'valueType' => 'string'
                    ],
                    'reminder_schedule_time' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.reminder_type === 'SCHEDULED_ABSOLUTE' && component.properties.is_recurring === false",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Time',
                        'description' => 'Time of the reminder.',
                        'valueType' => 'string'
                    ],
                    'reminder_schedule_offset_in_seconds' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.reminder_type === 'SCHEDULED_RELATIVE' && component.properties.is_recurring === false",
                        ],
                        'defaultValue' => '',
                        'name' => 'Offset in Seconds',
                        'description' => 'Offset in seconds for the reminder calculated relatively from the request date and time.',
                        'valueType' => 'string'
                    ],
                    'use_app_timezone' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Use App timezone?',
                        'description' => 'Set an timezone which fits your needs, otherwise it will use the devices timezone.',
                        'valueType' => 'boolean'
                    ],
                    'app_timezone' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.use_app_timezone === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'App Timezone',
                        'description' => 'Enabled only when use app timezone is enabled. Enter explicit timezone value.',
                        'valueType' => 'string'
                    ],
                    'spoken_info_content_text' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Spoken Info Content Text',
                        'description' => 'Contents of the reminder.',
                        'valueType' => 'string'
                    ],
                    'send_push_notification' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => true,
                        'name' => 'Should send push notification?',
                        'description' => 'When enabled, the Alexa App will notify the user on the device.',
                        'valueType' => 'boolean'
                    ],
                    'reminder_status_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Return Variable Name',
                        'description' => 'Variable name for the reminder status.',
                        'valueType' => 'string'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'name' => 'OK',
                        'description' => 'Executed if the reminder of an Alexa Skill could be created successfully.',
                        'valueType' => 'class'
                    ],
                    '_workflow' => 'read',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'create-alexa-reminder-element.html'
                    ],
                    '_factory' => new class ($this->_alexaRemindersApi, $this->_convoServiceDataProvider) implements IComponentFactory
                    {
                        private $_alexaRemindersApi;
                        private $_convoServiceDataProvider;

                        public function __construct($alexaRemindersApi, $convoServiceDataProvider)
                        {
                            $this->_alexaRemindersApi = $alexaRemindersApi;
                            $this->_convoServiceDataProvider = $convoServiceDataProvider;
                        }

                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Alexa\Elements\CreateAlexaReminderElement($properties, $this->_alexaRemindersApi, $this->_convoServiceDataProvider);
                        }
                    }
                ]
            ),
            new ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\UpdateAlexaReminderElement',
                'Update Alexa Reminder Element',
                'Updates an reminder in Alexa skill.',
                [
                    'reminder_id' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Reminder ID',
                        'description' => 'ID of the reminder to update.',
                        'valueType' => 'string'
                    ],
                    'is_recurring' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Is recurring?',
                        'description' => 'The reminder can be also recurring.',
                        'valueType' => 'boolean'
                    ],
                    'reminder_recurrence_start_date' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence Start Date',
                        'description' => 'Start date of the reminder recurrence.',
                        'valueType' => 'string'
                    ],
                    'reminder_recurrence_start_time' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence Start Time',
                        'description' => 'Start time of the reminder recurrence.',
                        'valueType' => 'string'
                    ],
                    'reminder_recurrence_end_date' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence End Date',
                        'description' => 'End date of the reminder recurrence.',
                        'valueType' => 'string'
                    ],
                    'reminder_recurrence_end_time' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence End Time',
                        'description' => 'End time of the reminder recurrence.',
                        'valueType' => 'string'
                    ],
                    'reminder_recurrence_rules' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Recurrence Rules',
                        'description' => 'Expressions which evaluates the recurrence rules of an reminder in RRULE format.',
                        'valueType' => 'string'
                    ],
                    'reminder_type' => [
                        'editor_type' => 'select',
                        'editor_properties' => [
                            'dependency' => "component.properties.is_recurring === false",
                            'options' => ['SCHEDULED_ABSOLUTE' => 'Absolute', 'SCHEDULED_RELATIVE' => 'Relative']
                        ],
                        'defaultValue' => 'SCHEDULED_ABSOLUTE',
                        'name' => 'Reminder Type',
                        'description' => 'Type of the reminder.',
                        'valueType' => 'string'
                    ],
                    'reminder_schedule_date' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.reminder_type === 'SCHEDULED_ABSOLUTE' && component.properties.is_recurring === false",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Date',
                        'description' => 'Date of the reminder.',
                        'valueType' => 'string'
                    ],
                    'reminder_schedule_time' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.reminder_type === 'SCHEDULED_ABSOLUTE' && component.properties.is_recurring === false",
                        ],
                        'defaultValue' => '',
                        'name' => 'Reminder Time',
                        'description' => 'Time of the reminder.',
                        'valueType' => 'string'
                    ],
                    'reminder_schedule_offset_in_seconds' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.reminder_type === 'SCHEDULED_RELATIVE' && component.properties.is_recurring === false",
                        ],
                        'defaultValue' => '',
                        'name' => 'Offset in Seconds',
                        'description' => 'Offset in seconds for the reminder calculated relatively from the request date and time.',
                        'valueType' => 'string'
                    ],
                    'use_app_timezone' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => false,
                        'name' => 'Use App timezone?',
                        'description' => 'Set an timezone which fits your needs, otherwise it will use the devices timezone.',
                        'valueType' => 'boolean'
                    ],
                    'app_timezone' => [
                        'editor_type' => 'text',
                        'editor_properties' => [
                            'dependency' => "component.properties.use_app_timezone === true",
                        ],
                        'defaultValue' => '',
                        'name' => 'App Timezone',
                        'description' => 'Enabled only when use app timezone is enabled. Enter explicit timezone value.',
                        'valueType' => 'string'
                    ],
                    'spoken_info_content_text' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Spoken Info Content Text',
                        'description' => 'Contents of the reminder.',
                        'valueType' => 'string'
                    ],
                    'send_push_notification' => [
                        'editor_type' => 'boolean',
                        'editor_properties' => [],
                        'defaultValue' => true,
                        'name' => 'Should send push notification?',
                        'description' => 'When enabled, the Alexa App will notify the user on the device.',
                        'valueType' => 'boolean'
                    ],
                    'reminder_status_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Return Variable Name',
                        'description' => 'Variable name for the reminder status.',
                        'valueType' => 'string'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'name' => 'OK',
                        'description' => 'Executed if the reminder of an Alexa Skill could be updated successfully.',
                        'valueType' => 'class'
                    ],
                    '_workflow' => 'read',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'update-alexa-reminder-element.html'
                    ],
                    '_factory' => new class ($this->_alexaRemindersApi, $this->_convoServiceDataProvider) implements IComponentFactory
                    {
                        private $_alexaRemindersApi;
                        private $_convoServiceDataProvider;

                        public function __construct($alexaRemindersApi, $convoServiceDataProvider)
                        {
                            $this->_alexaRemindersApi = $alexaRemindersApi;
                            $this->_convoServiceDataProvider = $convoServiceDataProvider;
                        }

                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Alexa\Elements\UpdateAlexaReminderElement($properties, $this->_alexaRemindersApi, $this->_convoServiceDataProvider);
                        }
                    }
                ]
            ),
            new ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\LoadAlexaRemindersElement',
                'Load Alexa Reminders Element',
                'Loads reminders of an Alexa skill.',
                [
                    'reminder_status' => [
                        'editor_type' => 'select',
                        'editor_properties' => [
                            'options' => ['ALL' => 'All', 'ON' => 'Active', 'COMPLETED' => 'Completed']
                        ],
                        'defaultValue' => 'ON',
                        'name' => 'Reminder Status',
                        'description' => 'Status of the reminder.',
                        'valueType' => 'string'
                    ],
                    'reminders_status_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Return Variable Name',
                        'description' => 'Variable name for the reminder status.',
                        'valueType' => 'string'
                    ],
                    'multiple' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Multiple',
                        'description' => 'Flow to be executed if more than one reminders could be found.',
                        'valueType' => 'class'
                    ],
                    'single' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true,
                            'hideWhenEmpty' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Single',
                        'description' => 'Flow to be executed if one reminder could be found.',
                        'valueType' => 'class'
                    ],
                    'empty' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true,
                            'hideWhenEmpty' => true
                        ],
                        'defaultValue' => [],
                        'defaultOpen' => false,
                        'name' => 'Empty',
                        'description' => 'Flow to be executed if no reminders could be found.',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            'Load Alexa Reminders of status <span class="statement"><b>{{ component.properties.reminder_status }}</b></span>' .
                            '</div>'
                    ],
                    '_workflow' => 'read',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'load-alexa-reminders-element.html'
                    ],
                    '_factory' => new class ($this->_alexaRemindersApi) implements IComponentFactory
                    {
                        private $_alexaRemindersApi;

                        public function __construct($alexaRemindersApi)
                        {
                            $this->_alexaRemindersApi = $alexaRemindersApi;
                        }

                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Alexa\Elements\LoadAlexaRemindersElement($properties, $this->_alexaRemindersApi);
                        }
                    }
                ]
            ),
            new ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\LoadAlexaReminderElement',
                'Load Alexa Reminder Element',
                'Loads reminder of an Alexa skill by its ID.',
                [
                    'reminder_id' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Reminder ID',
                        'description' => 'ID of the reminder.',
                        'valueType' => 'string'
                    ],
                    'reminder_status_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Return Variable Name',
                        'description' => 'Variable name for the reminder status.',
                        'valueType' => 'string'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'name' => 'OK',
                        'description' => 'Executed if the reminder for the current Alexa Skill could be found.',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            'Load Alexa Reminder by id <span class="statement"><b>{{ component.properties.reminder_id }}</b></span>' .
                            '</div>'
                    ],
                    '_workflow' => 'read',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'load-alexa-reminder-element.html'
                    ],
                    '_factory' => new class ($this->_alexaRemindersApi) implements IComponentFactory
                    {
                        private $_alexaRemindersApi;

                        public function __construct($alexaRemindersApi)
                        {
                            $this->_alexaRemindersApi = $alexaRemindersApi;
                        }

                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Alexa\Elements\LoadAlexaReminderElement($properties, $this->_alexaRemindersApi);
                        }
                    }
                ]
            ),
            new ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\DeleteAlexaReminderElement',
                'Delete Alexa Reminder Element',
                'Delete reminder of an Alexa skill.',
                [
                    'reminder_id' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => '',
                        'name' => 'Reminder ID',
                        'description' => 'ID of the reminder.',
                        'valueType' => 'string'
                    ],
                    'reminder_status_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'status',
                        'name' => 'Status',
                        'description' => 'Variable name for the deleted reminder status.',
                        'valueType' => 'string'
                    ],
                    'ok' => [
                        'editor_type' => 'service_components',
                        'editor_properties' => [
                            'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
                            'multiple' => true
                        ],
                        'defaultValue' => [],
                        'name' => 'OK',
                        'description' => 'Executed if the Alexa Reminder could be deleted successfully.',
                        'valueType' => 'class'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            'Delete Alexa Reminder with id <span class="statement"><b>{{ component.properties.reminder_id }}</b></span>' .
                            '</div>'
                    ],
                    '_workflow' => 'read',
                    '_help' =>  [
                        'type' => 'file',
                        'filename' => 'delete-alexa-reminder-element.html'
                    ],
                    '_factory' => new class ($this->_alexaRemindersApi) implements IComponentFactory
                    {
                        private $_alexaRemindersApi;

                        public function __construct($alexaRemindersApi)
                        {
                            $this->_alexaRemindersApi = $alexaRemindersApi;
                        }

                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Alexa\Elements\DeleteAlexaReminderElement($properties, $this->_alexaRemindersApi);
                        }
                    }
                ]
            ),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\PromptPermissionsConsentElement',
				'Prompt Permissions Consent Element',
				'Prompts the user to accept permissions used by your Alexa Skill',
				[
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'prompt-permissions-consent-element.html'
                    ),
					'_preview_angular' => [
						'type' => 'html',
						'template' => '<div class="code">' .
							'<span class="statement">PROMPT</span> ' .
							'<span>Permissions Consent</span>' .
							'</div>'
					],
					'_factory' => new class ($this->_convoServiceDataProvider) implements IComponentFactory
					{
						private $_convoServiceDataProvider;

						public function __construct($convoServiceDataProvider)
						{
							$this->_convoServiceDataProvider = $convoServiceDataProvider;
						}

						public function createComponent($properties, $service)
						{
							return new \Convo\Pckg\Alexa\Elements\PromptPermissionsConsentElement($properties, $this->_convoServiceDataProvider);
						}
					},
					'_workflow' => 'read'
				]
			),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\SimpleCardElement',
                'Simple Card Element',
                'Displays an card without image on Alexa devices with display, and in Activity View of the Alexa Companion App.',
                [
                    'title' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Title',
                        'description' => 'Expression which evaluates the title for the Simple Card',
                        'valueType' => 'string'
                    ],
                    'content' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Content',
                        'description' => 'Expression which evaluates the content for the Simple Card',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                            '<div class="code">' .
                                '<span class="statement">SIMPLE CARD</span> ' .
                                '<hr ng-if="component.properties.title || component.properties.content">' .
                                '<ul ng-if="component.properties.title || component.properties.content" class="list-unstyled">' .
                                    '<li ng-if="component.properties.title">Title: <b>{{component.properties.title}}</b></li>' .
                                    '<li ng-if="component.properties.content">Content: <b>{{component.properties.content}}</b></li>' .
                                '</ul>' .
                            '</div>'
                    ],
                    '_help' => [
                        'type' => 'file',
                        'filename' => 'simple-card-element.html'
                    ],
                    '_workflow' => 'read'
                ]
            ),
            new \Convo\Core\Factory\ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\StandardCardElement',
                'Standard Card Element',
                'Displays an card on Alexa devices with display, and in Activity View of the Alexa Companion App.',
                [
                    'title' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Title',
                        'description' => 'Expression which evaluates the title for the Standard Card',
                        'valueType' => 'string'
                    ],
                    'text' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Text',
                        'description' => 'Expression which evaluates the text for the Standard Card',
                        'valueType' => 'string'
                    ],
                    'small_image_url' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Small Image URL',
                        'description' => 'Expression which evaluates the small Image URL for the Standard Card',
                        'valueType' => 'string'
                    ],
                    'large_image_url' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => null,
                        'name' => 'Large Image URL',
                        'description' => 'Expression which evaluates the large Image URL for the Standard Card',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' =>
                            '<div class="code">' .
                                '<span class="statement">STANDARD CARD</span> ' .
                                '<hr ng-if="component.properties.title || component.properties.text">' .
                                '<ul ng-if="component.properties.title || component.properties.text" class="list-unstyled">' .
                                    '<li ng-if="component.properties.title">Title: <b>{{component.properties.title}}</b></li>' .
                                    '<li ng-if="component.properties.text">Text: <b>{{component.properties.text}}</b></li>' .
                                '</ul>' .
                                '<div ng-if="component.properties.small_image_url" class="row align-items-center">'.
                                    '<div class="col-xs-4">'.
                                        '<div class="title">Small Image: </div>' .
                                    '</div>'.
                                    '<div class="col-xs-8">'.
                                        '<img onerror="this.src=\'https://place-hold.it/60x60/\'" src="{{component.properties.small_image_url}}" width="60" height="60" alt="SMALL IMG">'.
                                    '</div>'.
                                '</div>'.
                                '<hr ng-if="component.properties.small_image_url && component.properties.large_image_url">'.
                                '<div ng-if="component.properties.large_image_url" class="row align-items-center">'.
                                    '<div class="col-xs-4">'.
                                        '<div class="title">Large Image: </div>' .
                                    '</div>'.
                                    '<div class="col-xs-8">'.
                                        '<img onerror="this.src=\'https://place-hold.it/100x100/\'" src="{{component.properties.large_image_url}}" width="100" height="100" alt="LARGE IMG">'.
                                    '</div>'.
                                '</div>'.
                            '</div>'
                    ],
                    '_help' => [
                        'type' => 'file',
                        'filename' => 'standard-card-element.html'
                    ],
                    '_workflow' => 'read'
                ]
            ),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\GenericAplElement',
				'Generic APL Element',
				'Prepares an APL response from an APL definition.',
				array(
					'use_hashtag_sign' => array(
						'editor_type' => 'boolean',
						'editor_properties' => array(),
						'defaultValue' => false,
						'name' => 'Use #{} for evaluation?',
						'description' => 'Since Alexa APL uses ${} as their data-binding syntax, it clashes with our data binding syntax. To avoid that just enable the option and start evaluating via #{}.',
						'valueType' => 'boolean'
					),
					'name' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Template Token',
						'description' => 'Template Token of the APL definition.',
						'valueType' => 'string'
					),
					'apl_definition' => array(
						'editor_type' => 'desc',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'APL Definition',
						'description' => 'Definition on an APL document.',
						'valueType' => 'string'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'generic-epl-element.html'
					),
					'_workflow' => 'read',
					'_platform_defaults' => array(
						'amazon' => array(
							'interfaces' => array('ALEXA_PRESENTATION_APL')
						)
					),
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplExecuteCommandsElement',
				'APL Execute Commands',
				'Prepares an APL Execute Commands Response of APL Set Value Command Element children.',
				array(
					'name' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Template Token',
						'description' => 'Template Token of the APL document that the commands will be executed on..',
						'valueType' => 'string'
					),
					'apl_commands' => array(
						'editor_type' => 'service_components',
						'editor_properties' => array(
							'allow_interfaces' => [\Convo\Pckg\Alexa\Workflow\IAplCommandElement::class],
							'multiple' => true
						),
						'defaultValue' => array(),
						'defaultOpen' => true,
						'name' => 'APL Commands',
						'description' => 'APL Commands to be added.',
						'valueType' => 'class'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-execute-commands-element.html'
					),
					'_workflow' => 'read',
					'_platform_defaults' => array(
						'amazon' => array(
							'interfaces' => array('ALEXA_PRESENTATION_APL')
						)
					),
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Filters\AplUserEventReader',
				'APL User Event',
				'Reads APL User Events. Use for matching specific APL User Event Argument Part or any APL User Event.',
				array(
					'use_apl_user_event_argument_part' => array(
						'editor_type' => 'boolean',
						'editor_properties' => array(),
						'defaultValue' => false,
						'name' => 'Use APL User Event Argument Part',
						'description' => 'If this value is false, any APL User Event will be caught. Otherwise, only specified APL User Event name will be caught.',
						'valueType' => 'boolean'
					),
					'apl_user_event_argument_part' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.use_apl_user_event_argument_part === true"
						),
						'defaultValue' => '',
						'name' => 'APL User Event Argument Part',
						'description' => 'Name of the APL User Event which activates this filter.',
						'valueType' => 'string'
					),
					'_preview_angular' => array(
						'type' => 'html',
						'template' => '<div class="code">Catch <b>{{ !component.properties.use_apl_user_event_argument_part ? "Any " : ""}}</b> APL User Event <b>{{ component.properties.use_apl_user_event_argument_part ? "Argument Part " + component.properties.apl_user_event_argument_part : ""}}</b></div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-user-event-reader.html'
					),
					'_workflow' => 'filter',
					'_descend' => true,
				)
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\AplCommandElement',
				'APL Command Element',
				'Prepares an APL command.',
				array(
					'command_type' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'options' => [
								IAplCommandElement::APL_COMMAND_TYPE_AUTO_PAGE => IAplCommandElement::APL_COMMAND_TYPE_AUTO_PAGE,
								IAplCommandElement::APL_COMMAND_TYPE_CLEAR_FOCUS => IAplCommandElement::APL_COMMAND_TYPE_CLEAR_FOCUS,
								IAplCommandElement::APL_COMMAND_TYPE_FINISH => IAplCommandElement::APL_COMMAND_TYPE_FINISH,
								IAplCommandElement::APL_COMMAND_TYPE_REINFLATE => IAplCommandElement::APL_COMMAND_TYPE_REINFLATE,
								IAplCommandElement::APL_COMMAND_TYPE_BACKSTACK_CLEAR => IAplCommandElement::APL_COMMAND_TYPE_BACKSTACK_CLEAR,
								IAplCommandElement::APL_COMMAND_TYPE_BACK_GO_BACK => IAplCommandElement::APL_COMMAND_TYPE_BACK_GO_BACK,
								IAplCommandElement::APL_COMMAND_TYPE_IDLE => IAplCommandElement::APL_COMMAND_TYPE_IDLE,
								IAplCommandElement::APL_COMMAND_TYPE_OPEN_URL => IAplCommandElement::APL_COMMAND_TYPE_OPEN_URL,
								IAplCommandElement::APL_COMMAND_TYPE_SCROLL => IAplCommandElement::APL_COMMAND_TYPE_SCROLL,
								IAplCommandElement::APL_COMMAND_TYPE_SCROLL_TO_COMPONENT => IAplCommandElement::APL_COMMAND_TYPE_SCROLL_TO_COMPONENT,
								IAplCommandElement::APL_COMMAND_TYPE_SCROLL_TO_INDEX => IAplCommandElement::APL_COMMAND_TYPE_SCROLL_TO_INDEX,
								IAplCommandElement::APL_COMMAND_TYPE_SEND_EVENT => IAplCommandElement::APL_COMMAND_TYPE_SEND_EVENT,
								IAplCommandElement::APL_COMMAND_TYPE_SET_FOCUS => IAplCommandElement::APL_COMMAND_TYPE_SET_FOCUS,
								IAplCommandElement::APL_COMMAND_TYPE_SET_VALUE => IAplCommandElement::APL_COMMAND_TYPE_SET_VALUE,
								IAplCommandElement::APL_COMMAND_TYPE_SPEAK_ITEM => IAplCommandElement::APL_COMMAND_TYPE_SPEAK_ITEM,
								IAplCommandElement::APL_COMMAND_TYPE_SPEAK_LIST => IAplCommandElement::APL_COMMAND_TYPE_SPEAK_LIST,
							]
						],
						'defaultValue' => IAplCommandElement::APL_COMMAND_TYPE_FINISH,
						'name' => 'APL Command Type',
						'description' => 'Type of the command.',
						'valueType' => 'string'
					),
					'command_auto_page_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'AutoPage'",
						],
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the "Pager" to page through.',
						'valueType' => 'string'
					),
					'command_auto_page_count' => array(
						'editor_type' => 'text',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'AutoPage'",
						],
						'defaultValue' => '',
						'name' => 'Count',
						'description' => 'The number of pages to display.',
						'valueType' => 'string'
					),
					'command_auto_page_duration' => array(
						'editor_type' => 'text',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'AutoPage'",
						],
						'defaultValue' => '',
						'name' => 'Duration',
						'description' => 'The amount of time (in milliseconds) to wait after advancing to the next page.',
						'valueType' => 'string'
					),
					'command_auto_page_delay' => array(
						'editor_type' => 'text',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'AutoPage'",
						],
						'defaultValue' => 1000,
						'name' => 'Delay',
						'description' => 'Displays page 1 for value in ms while waiting to start.',
						'valueType' => 'string'
					),
					'command_back_go_back_use_back_type' => array(
						'editor_type' => 'boolean',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'Back:GoBack'",
						],
						'defaultValue' => false,
						'name' => 'Use APL Back Type',
						'description' => 'If this value is false, back will go to the previous rendered document, otherwise it will navigate to an specified back type property.',
						'valueType' => 'boolean'
					),
					'command_back_go_back_back_type' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'Back:GoBack' && component.properties.command_back_go_back_use_back_type === true",
							'options' => ['count' => 'Count', 'index' => 'Index', 'id' => 'ID']
						],
						'defaultValue' => 'count',
						'name' => 'Back Type',
						'description' => 'The type of back navigation to use.',
						'valueType' => 'string'
					),
					'command_back_go_back_back_value' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'Back:GoBack' && component.properties.command_back_go_back_use_back_type === true"
						),
						'defaultValue' => 0,
						'name' => 'APL Back Value',
						'description' => 'The value indicating the document to return to in the backstack.',
						'valueType' => 'string'
					),
					'command_idle_delay' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'Idle'"
						),
						'defaultValue' => 3000,
						'name' => 'Delay',
						'description' => 'Numeric value of the delay to set in milliseconds.',
						'valueType' => 'string'
					),
					'command_open_url_source' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'OpenURL'"
						),
						'defaultValue' => '',
						'name' => 'Source',
						'description' => 'The URL to open.',
						'valueType' => 'string'
					),
					'command_scroll_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'Scroll'"
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component to read.',
						'valueType' => 'string'
					),
					'command_scroll_distance' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'Scroll'"
						),
						'defaultValue' => '',
						'name' => 'Distance',
						'description' => 'The number of pages to scroll. Defaults to 1.',
						'valueType' => 'string'
					),
					'command_scroll_to_component_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'ScrollToComponent'"
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component.',
						'valueType' => 'string'
					),
					'command_scroll_to_component_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'ScrollToComponent'",
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item after scrolling.',
						'valueType' => 'string'
					),
					'command_scroll_to_index_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'ScrollToIndex'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component.',
						'valueType' => 'string'
					),
					'command_scroll_to_index_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'ScrollToIndex'",
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item after scrolling.',
						'valueType' => 'string'
					),
					'command_scroll_to_index_index' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'ScrollToIndex'",
						),
						'defaultValue' => 0,
						'name' => 'Index',
						'description' => 'The 0-based index of the child to display.',
						'valueType' => 'string'
					),
					'command_send_event_arguments' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SendEvent'",
						),
						'defaultValue' => [],
						'name' => 'Arguments',
						'description' => 'An array of argument data to send to the skill in the "UserEvent" request.',
						'valueType' => 'array'
					),
					'command_send_event_components' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SendEvent'",
						),
						'defaultValue' => [],
						'name' => 'Components',
						'description' => 'An array of component IDs. The value associated with each identified component is included in the the resulting "UserEvent" request.',
						'valueType' => 'array'
					),
					'command_set_focus_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SetFocus'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component which will receive focus.',
						'valueType' => 'string'
					),
					'command_set_value_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SetValue'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'Refers to an component id of current APL Definition.',
						'valueType' => 'string'
					),
					'command_set_value_property' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SetValue'",
						),
						'defaultValue' => '',
						'name' => 'Property',
						'description' => 'Property to change.',
						'valueType' => 'string'
					),
					'command_set_value_value' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SetValue'",
						),
						'defaultValue' => '',
						'name' => 'Value',
						'description' => 'Value to set.',
						'valueType' => 'string'
					),
					'command_speak_item_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakItem'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The ID of the component.',
						'valueType' => 'string'
					),
					'command_speak_item_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'SpeakItem'",
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item after scrolling. Defaults to "visible".',
						'valueType' => 'string'
					),
					'command_speak_item_highlight_mode' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'SpeakItem'",
							'options' => ['line' => 'Line', 'block' => 'Block']
						],
						'defaultValue' => 'block',
						'name' => 'Highlight Mode',
						'description' => 'How karaoke is applied: on a line-by-line basis, or to the entire block. Defaults to "block".',
						'valueType' => 'string'
					),
					'command_speak_item_minimum_dwell_time' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakItem'",
						),
						'defaultValue' => '',
						'name' => 'Minimum Dwell Time',
						'description' => 'The minimum number of milliseconds that an item will be highlighted.',
						'valueType' => 'string'
					),
					'command_speak_list_component_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakList'",
						),
						'defaultValue' => '',
						'name' => 'Component ID',
						'description' => 'The id of the Sequence or Container (or any other hosting component).',
						'valueType' => 'string'
					),
					'command_speak_list_align' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.command_type === 'SpeakList'",
							'options' => ['first' => 'First', 'center' => 'Center', 'last' => 'Last', 'visible' => 'Visible']
						],
						'defaultValue' => 'visible',
						'name' => 'Align',
						'description' => 'The alignment of the item. Defaults to "visible".',
						'valueType' => 'string'
					),
					'command_speak_list_count' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakList'",
						),
						'defaultValue' => 1,
						'name' => 'Count',
						'description' => 'The number of children to read.',
						'valueType' => 'string'
					),
					'command_speak_list_start' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakList'",
						),
						'defaultValue' => 0,
						'name' => 'Start',
						'description' => 'The index of the item to start reading.',
						'valueType' => 'string'
					),
					'command_speak_list_minimum_dwell_time' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.command_type === 'SpeakList'",
						),
						'defaultValue' => '',
						'name' => 'Minimum Dwell Time',
						'description' => 'The minimum number of milliseconds that an item will be highlighted for. Defaults to 0.',
						'valueType' => 'string'
					),
					'command_description' => array(
						'editor_type' => 'desc',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Description',
						'description' => 'Optional documentation for this command.',
						'valueType' => 'string'
					),
					'command_delay' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'Delay',
						'description' => 'Delay time in milliseconds before this command runs. Must be non-negative. Defaults to 0.',
						'valueType' => 'string'
					),
					'command_screen_lock' => array(
						'editor_type' => 'boolean',
						'editor_properties' => array(),
						'defaultValue' => false,
						'name' => 'Screen Lock',
						'description' => 'If true, disable the interaction timer.',
						'valueType' => 'boolean'
					),
					'command_when' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => '',
						'name' => 'When',
						'description' => 'Conditional expression. If this evaluates to false, the command is skipped. Defaults to true.',
						'valueType' => 'string'
					),
					'_workflow' => 'read',
					'_preview_angular' => array(
						'type' => 'html',
						//'template' => '<div class="code">APL Command <b>{{component.properties.command_type}}</b>.</div>'
						'template' =>'<div class="code">' .
							'<span>APL Command <b>{{component.properties.command_type}}</b></span>' .
							'<hr>' .
							'<ul class="list-unstyled">' .
							'<li ng-if="component.properties.command_type === \'AutoPage\'">Component ID: <b>{{component.properties.command_auto_page_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'AutoPage\'">Count: <b>{{component.properties.command_auto_page_count}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'AutoPage\'">Duration: <b>{{component.properties.command_auto_page_duration}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'AutoPage\'">Delay: <b>{{component.properties.command_auto_page_delay}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'AutoPage\'">' .
							'<li ng-if="component.properties.command_type === \'Back:GoBack\'">Back Type: <b>{{component.properties.command_back_go_back_back_type}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'Back:GoBack\'">Back Value: <b>{{component.properties.command_back_go_back_back_value}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'Back:GoBack\'">' .
							'<li ng-if="component.properties.command_type === \'Idle\'">Delay: <b>{{component.properties.command_idle_delay}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'Idle\'">' .
							'<li ng-if="component.properties.command_type === \'OpenURL\'">Source: <b>{{component.properties.command_open_url_source}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'OpenURL\'">' .
							'<li ng-if="component.properties.command_type === \'Scroll\'">Component ID: <b>{{component.properties.command_scroll_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'Scroll\'">Distance: <b>{{component.properties.command_scroll_distance}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'Scroll\'">' .
							'<li ng-if="component.properties.command_type === \'ScrollToComponent\'">Component ID: <b>{{component.properties.command_scroll_to_component_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'ScrollToComponent\'">Align: <b>{{component.properties.command_scroll_to_component_align}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'ScrollToComponent\'">' .
							'<li ng-if="component.properties.command_type === \'ScrollToIndex\'">Component ID: <b>{{component.properties.command_scroll_to_index_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'ScrollToIndex\'">Align: <b>{{component.properties.command_scroll_to_index_align}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'ScrollToIndex\'">Index: <b>{{component.properties.command_scroll_to_index_index}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'ScrollToIndex\'">' .
							'<li ng-if="component.properties.command_type === \'SendEvent\'">Arguments: <b>{{component.properties.command_send_event_arguments}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SendEvent\'">Components: <b>{{component.properties.command_send_event_components}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SendEvent\'">' .
							'<li ng-if="component.properties.command_type === \'SetFocus\'">Component ID: <b>{{component.properties.command_set_focus_component_id}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SetFocus\'">' .
							'<li ng-if="component.properties.command_type === \'SetValue\'">Component ID: <b>{{component.properties.command_set_value_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SetValue\'">Property: <b>{{component.properties.command_set_value_property}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SetValue\'">Value: <b>{{component.properties.command_set_value_value}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SetValue\'">' .
							'<li ng-if="component.properties.command_type === \'SpeakItem\'">Component ID: <b>{{component.properties.command_speak_item_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakItem\'">Align: <b>{{component.properties.command_speak_item_align}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakItem\'">Highlight Mode: <b>{{component.properties.command_speak_item_highlight_mode}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakItem\'">Minimum Dwell Time: <b>{{component.properties.command_speak_item_minimum_dwell_time}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SpeakItem\'">' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Component ID: <b>{{component.properties.command_speak_list_component_id}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Align: <b>{{component.properties.command_speak_list_align}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Start: <b>{{component.properties.command_speak_list_start}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Count: <b>{{component.properties.command_speak_list_count}}</b></li>' .
							'<li ng-if="component.properties.command_type === \'SpeakList\'">Minimum Dwell Time: <b>{{component.properties.command_speak_list_minimum_dwell_time}}</b></li>' .
							'<hr ng-if="component.properties.command_type === \'SpeakList\'">' .
							' <li>Command Description: <b>{{component.properties.command_description}}</b></li>' .
							' <li>Command Delay: <b>{{component.properties.command_delay}}</b></li>' .
							' <li>Command Screen Lock: <b>{{component.properties.command_screen_lock}}</b></li>' .
							' <li>Command When: <b>{{component.properties.command_when}}</b></li>' .
							'</ul>' .
							'</div>'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'apl-commands-element.html'
					),
					'_descend' => true,
				)
			),
			new ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\GetInSkillProductsElement',
				'Get In-Skill Products Element',
				'Get In-Skill Products.',
				[
					'name' => [
						'editor_type' => 'text',
						'editor_properties' => [],
						'defaultValue' => 'status',
						'name' => 'Name',
						'description' => 'Name under which to store the loaded user object in the context',
						'valueType' => 'string'
					],
					'should_get_product_by_id' => array (
						'editor_type' => 'boolean',
						'editor_properties' => array(),
						'defaultValue' => false,
						'name' => 'Get single product by ID?',
						'description' => 'Get only one product by productId.',
						'valueType' => 'boolean'
					),
					'product_id' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.should_get_product_by_id === true",
						),
						'defaultValue' => '',
						'name' => 'Product ID',
						'description' => 'A valid product ID.',
						'valueType' => 'string'
					),
					'filter_by_entitlement' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.should_get_product_by_id === false",
							'options' => ['all' => 'All', 'entitled' => 'Purchased', 'not_entitled' => 'Not Purchased']
						],
						'defaultValue' => 'all',
						'name' => 'Filter by Entitlement',
						'description' => 'Filter products based on whether the user is entitled to the product.',
						'valueType' => 'string'
					),
					'filter_by_product_type' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'dependency' => "component.properties.should_get_product_by_id === false",
							'options' => ['all' => 'All', 'consumable' => 'Consumable', 'subscription' => 'Subscription', 'entitlement' => 'Entitlement']
						],
						'defaultValue' => 'all',
						'name' => 'Filter by product type',
						'description' => 'Filter products based on the product type which can be: consumable, subscription or entitlement.',
						'valueType' => 'string'
					),
					'_preview_angular' => [
						'type' => 'html',
						'template' => '<div class="code">' .
							'<span ng-if="component.properties.should_get_product_by_id === false">Load <span class="statement"><b>{{ component.properties.filter_by_entitlement }}</b></span> In Skill Products types <span class="statement"><b>{{ component.properties.filter_by_product_type }}</b></span> and set them as <span class="statement"><b>{{ component.properties.name }}</b></span></span>' .
							'<span ng-if="component.properties.should_get_product_by_id === true">Load In Skill Product by id <span class="statement"><b>{{ component.properties.product_id }}</b></span></span>' .
							'</div>'
					],
					'_workflow' => 'read',
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'get-in-skill-products-element.html'
					),
					'_factory' => new class ($this->_httpFactory) implements IComponentFactory
					{
						private $_httpFactory;

						public function __construct($httpFactory)
						{
							$this->_httpFactory = $httpFactory;
						}

						public function createComponent($properties, $service)
						{
							return new \Convo\Pckg\Alexa\Elements\GetInSkillProductsElement($properties, $this->_httpFactory);
						}
					}
				]
			),
			new ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\SalesDirectiveElement',
				'Sales Directive Element',
				'Sales Directive Element sends a Buy, Upsell or Refund/Cancel request.',
				[
					'name' => [
						'editor_type' => 'text',
						'editor_properties' => [],
						'defaultValue' => 'status',
						'name' => 'Token',
						'description' => 'A token to identify this message exchange and store skill information. The token is not used by Alexa, but is returned in the resulting Connections.Response. You provide this token in a format that makes sense for the skill.',
						'valueType' => 'string'
					],
					'sales_directive' => array(
						'editor_type' => 'select',
						'editor_properties' => [
							'options' => ['buy' => 'Buy', 'upsell' => 'Upsell', 'cancel' => 'Refund/Cancel']
						],
						'defaultValue' => 'buy',
						'name' => 'Sales Directive',
						'description' => 'Initiates the Buy, Upsell or Refund/Cancel request type.',
						'valueType' => 'string'
					),
					'product_filter_value' => [
						'editor_type' => 'text',
						'editor_properties' => [],
						'defaultValue' => '',
						'name' => 'Product Filter Value',
						'description' => 'Match of product name.',
						'valueType' => 'string'
					],
					'product_upsell_var' => array(
						'editor_type' => 'text',
						'editor_properties' => array(
							'dependency' => "component.properties.sales_directive === 'upsell'",
						),
						'defaultValue' => 'product_upsell_var',
						'name' => 'Product Upsell Var',
						'description' => 'Where to store the product to use in the "product_upsell_message" property.',
						'valueType' => 'string'
					),
					'product_upsell_message' => array(
						'editor_type' => 'ssml',
						'editor_properties' => array(
							'dependency' => "component.properties.sales_directive === 'upsell'",
						),
						'defaultValue' => '',
						'name' => 'Upsell Message',
						'description' => 'A product suggestion that fits the current user context. Should always end with an explicit confirmation question.',
						'valueType' => 'string'
					),
					'on_product_not_found' => [
						'editor_type' => 'service_components',
						'editor_properties' => [
							'allow_interfaces' => ['\Convo\Core\Workflow\IConversationElement'],
							'multiple' => true
						],
						'defaultValue' => [],
						'name' => 'Product Not Found',
						'description' => 'Executed if the product was not found.',
						'valueType' => 'class'
					],
					'_preview_angular' => [
						'type' => 'html',
						'template' => '<div class="code">' .
								'<div>Execute <span class="statement"><b>{{ component.properties.sales_directive }}</b></span> for <span class="statement"><b>{{ component.properties.product_filter_value }}</b></span></div>' .
								'<hr ng-if="component.properties.sales_directive === \'upsell\'"/>' .
								'<div ng-if="component.properties.sales_directive === \'upsell\'">Upsell Message: <b>{{ component.properties.product_upsell_message }}</b></div>' .
							'</div>'
					],
					'_workflow' => 'read',
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'sales-directive-element.html'
					),
					'_factory' => new class ($this->_httpFactory) implements IComponentFactory
					{
						private $_httpFactory;

						public function __construct($httpFactory)
						{
							$this->_httpFactory = $httpFactory;
						}

						public function createComponent($properties, $service)
						{
							return new \Convo\Pckg\Alexa\Elements\SalesDirectiveElement($properties, $this->_httpFactory);
						}
					}
				]
			),
			new \Convo\Core\Factory\ComponentDefinition(
				$this->getNamespace(),
				'\Convo\Pckg\Alexa\Elements\SalesBlock',
				'Sales Block',
				'A special role "sales_block" block, that handles Buy,Upsell and Refund/Cancel requests (not in standard service session).',
				array(
					'role' => array(
						'defaultValue' => IRunnableBlock::ROLE_SALES_BLOCK
					),
					'block_id' => array(
						'editor_type' => 'block_id',
						'editor_properties' => array(),
						'defaultValue' => 'new-block-id',
						'name' => 'Block ID',
						'description' => 'Unique string identificator',
						'valueType' => 'string'
					),
					'sales_status_var' => array(
						'editor_type' => 'text',
						'editor_properties' => array(),
						'defaultValue' => 'sales_status',
						'name' => 'Sales Status',
						'description' => 'Variable name for the sales status array',
						'valueType' => 'string'
					),
					'no_buy' => array(
						'editor_type' => 'service_components',
						'editor_properties' => array(
							'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
							'multiple' => true
						),
						'defaultValue' => array(),
						'name' => 'Buy',
						'description' => 'Elements to be read if next song is requested but not available',
						'valueType' => 'class'
					),
					'no_upsell' => array(
						'editor_type' => 'service_components',
						'editor_properties' => array(
							'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
							'multiple' => true
						),
						'defaultValue' => array(),
						'name' => 'Upsell',
						'description' => 'Elements to be read if previous song is requested but not available',
						'valueType' => 'class'
					),
					'no_refund_cancel' => array(
						'editor_type' => 'service_components',
						'editor_properties' => array(
							'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
							'multiple' => true
						),
						'defaultValue' => array(),
						'name' => 'Refund/Cancel',
						'description' => 'Elements to be read if previous song is requested but not available',
						'valueType' => 'class'
					),
					'fallback' => array(
						'editor_type' => 'service_components',
						'editor_properties' => array(
							'allow_interfaces' => array('\Convo\Core\Workflow\IConversationElement'),
							'multiple' => true
						),
						'defaultValue' => array(),
						'name' => 'Fallback',
						'description' => 'Elements to be read if none of the processors match',
						'valueType' => 'class'
					),
					'_help' =>  array(
						'type' => 'file',
						'filename' => 'sales-block.html'
					),
					'_interface' => '\Convo\Core\Workflow\IConversationElement',
					'_workflow' => 'read',
					'_system' => true,
					'_factory' => new class () implements \Convo\Core\Factory\IComponentFactory
					{
						public function createComponent( $properties, $service)
						{
							return new \Convo\Pckg\Alexa\Elements\SalesBlock( $properties, $service);
						}
					}
				)
			),
        ];
    }

}
