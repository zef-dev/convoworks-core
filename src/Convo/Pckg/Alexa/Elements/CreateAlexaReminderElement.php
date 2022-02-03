<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Alexa\Api\AlexaRemindersApi;
use Convo\Core\Factory\InvalidComponentDataException;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class CreateAlexaReminderElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    const SCHEDULED_ABSOLUTE = 'SCHEDULED_ABSOLUTE';
    const SCHEDULED_RELATIVE = 'SCHEDULED_RELATIVE';
    const REMINDER_SCHEDULE_DATE_FORMAT = "Y-m-d\TH:i:s";

    /**
     * @var bool
     */
    private $_isRecurring;

    /**
     * @var string
     */
    private $_reminderType;

    /**
     * @var string
     */
    private $_reminderScheduleDate;

    /**
     * @var string
     */
    private $_reminderScheduleTime;

    /**
     * @var string
     */
    private $_offsetInSeconds;

    /**
     * @var bool
     */
    private $_useAppTimezone;

    /**
     * @var string
     */
    private $_appTimezone;

    /**
     * @var string
     */
    private $_recurrenceStartDate;

    /**
     * @var string
     */
    private $_recurrenceEndDate;

    /**
     * @var string
     */
    private $_recurrenceStartTime;

    /**
     * @var string
     */
    private $_recurrenceEndTime;

    /**
     * @var string
     */
    private $_recurrenceRules;

    /**
     * @var string
     */
    private $_spokenInfoContentText;

    /**
     * @var bool
     */
    private $_sendPushNotification;

    /**
     * @var string
     */
    private $_statusVar;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_ok = array();

    /**
     * @var AlexaRemindersApi
     */
    private $_alexaRemindersApi;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    public function __construct($properties, $alexaRemindersApi, $convoServiceDataProvider)
    {
        parent::__construct($properties);

        $this->_isRecurring = $properties['is_recurring'];
        $this->_reminderType = $properties['reminder_type'];
        $this->_reminderScheduleDate = $properties['reminder_schedule_date'];
        $this->_reminderScheduleTime = $properties['reminder_schedule_time'];
        $this->_offsetInSeconds = $properties['reminder_schedule_offset_in_seconds'];
        $this->_useAppTimezone = $properties['use_app_timezone'];
        $this->_appTimezone = $properties['app_timezone'];
        $this->_recurrenceStartDate = $properties['reminder_recurrence_start_date'];
        $this->_recurrenceStartTime = $properties['reminder_recurrence_start_time'];
        $this->_recurrenceEndDate = $properties['reminder_recurrence_end_date'];
        $this->_recurrenceEndTime = $properties['reminder_recurrence_end_time'];
        $this->_recurrenceRules = $properties['reminder_recurrence_rules'];
        $this->_spokenInfoContentText = $properties['spoken_info_content_text'];
        $this->_sendPushNotification = $properties['send_push_notification'];
        $this->_statusVar = $properties['reminder_status_var'];


        foreach ($properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }

        $this->_alexaRemindersApi = $alexaRemindersApi;
        $this->_convoServiceDataProvider = $convoServiceDataProvider;
    }

    /**
     * @param IConvoRequest $request
     * @param IConvoResponse $response
     */
    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $isRecurring = $this->evaluateString($this->_isRecurring);
        $reminderType = $this->evaluateString($this->_reminderType);
        $scheduleDate = $this->evaluateString($this->_reminderScheduleDate);
        $scheduleTime = $this->evaluateString($this->_reminderScheduleTime);
        $offsetInSeconds = $this->evaluateString($this->_offsetInSeconds);
        $useAppTimezone = $this->evaluateString($this->_useAppTimezone);
        $appTimezone = $this->evaluateString($this->_appTimezone);
        $recurrenceStartDate = $this->evaluateString($this->_recurrenceStartDate);
        $recurrenceEndDate = $this->evaluateString($this->_recurrenceEndDate);
        $recurrenceStartTime = $this->evaluateString($this->_recurrenceStartTime);
        $recurrenceEndTime = $this->evaluateString($this->_recurrenceEndTime);
        $recurrenceRules = $this->evaluateString($this->_recurrenceRules);
        $spokenInfoContentText = $this->evaluateString($this->_spokenInfoContentText);
        $sendPushNotification = $this->evaluateString($this->_sendPushNotification);

        $statusVar = $this->evaluateString($this->_statusVar);

        if (is_a($request, AmazonCommandRequest::class)) {
            $payload = [
                'requestTime' => date(self::REMINDER_SCHEDULE_DATE_FORMAT, time()),
                'trigger' => [
                    'type' => self::SCHEDULED_ABSOLUTE
                ]
            ];

            if ($isRecurring) {
                if (!empty($recurrenceStartDate) && !empty($recurrenceStartTime) &&
                    !empty($recurrenceEndDate) && !empty($recurrenceEndTime)) {
                    $startDateTime = date_create($recurrenceStartDate . ' ' . $recurrenceStartTime);
                    $endDateTime = date_create($recurrenceEndDate . ' ' . $recurrenceEndTime);
                    $payload['trigger']['recurrence']['startDateTime'] = $startDateTime->format(self::REMINDER_SCHEDULE_DATE_FORMAT);
                    $payload['trigger']['recurrence']['endDateTime'] = $endDateTime->format(self::REMINDER_SCHEDULE_DATE_FORMAT);
                }
                $payload['trigger']['recurrence']['recurrenceRules'] = [$recurrenceRules];
            } else {
                $payload['trigger']['type'] = $reminderType;
                switch ($reminderType) {
                    case self::SCHEDULED_ABSOLUTE:
                        $dateTime = date_create($scheduleDate . ' ' . $scheduleTime);
                        $payload['trigger']['scheduledTime'] = $dateTime->format(self::REMINDER_SCHEDULE_DATE_FORMAT);
                        break;
                    case self::SCHEDULED_RELATIVE:
                        $payload['trigger']['offsetInSeconds'] = intval($offsetInSeconds);
                        break;
                    default:
                        throw new InvalidComponentDataException('Unsupported schedule type [' . $reminderType . ']');
                }
            }

            if ($useAppTimezone) {
                $dateTimeZone = new \DateTimeZone($appTimezone);
                $payload['trigger']['timeZoneId'] = $dateTimeZone->getName();
            }

            // get supported locale from our meta data
            $supportedLocales = $this->_convoServiceDataProvider->getServiceMeta(
                new RestSystemUser(),
                $this->getService()->getId()
            )['supported_locales'];

            // add spoken content for each supported locale
            $spokenContent = [];
            foreach ($supportedLocales as $supportedLocale) {
                $spokenContent[] = [
                    'locale' => $supportedLocale,
                    'text' => $spokenInfoContentText,
                    'ssml' => '<speak>'.$spokenInfoContentText.'</speak>'
                ];
            }
            $payload['alertInfo'] = [
                'spokenInfo' => [
                    'content' => $spokenContent
                ]
            ];

            $payload['pushNotification']['status'] = $sendPushNotification ? 'ENABLED' : 'DISABLED';

            $this->_logger->info('Going to create a reminder with the following payload [' . json_encode($payload) . ']');

            $data = $this->_alexaRemindersApi->createReminder($request, $payload);

            $this->_logger->info('Created reminder [' . json_encode($data) . ']');

            $params = $this->getService()->getComponentParams(IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
            $params->setServiceParam($statusVar, ['reminder' => $data]);

            foreach ($this->_ok as $element) {
                $element->read($request, $response);
            }
        }
    }
}
