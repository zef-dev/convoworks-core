<?php

namespace Convo\Core\Adapters\Alexa\Api;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\Rest\InvalidRequestException;
use Convo\Core\Util\IHttpFactory;
use PHPUnit\Util\Exception;
use Psr\Http\Client\ClientExceptionInterface;

class AlexaRemindersApi extends AlexaApi
{
    public function __construct($logger, $httpFactory)
    {
        parent::__construct($logger, $httpFactory);
    }

    /**
     * @param AmazonCommandRequest $request
     * @param string $id
     * @return mixed
     * @throws DataItemNotFoundException
     * @throws InsufficientPermissionsGrantedException
     */
    public function getReminder(AmazonCommandRequest $request, string $id) {
        try {
            return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, '/v1/alerts/reminders/' . $id);
        } catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 401:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::alerts:reminders:skill:read] permissions.', null, $e);
                case 404:
                    throw new DataItemNotFoundException(`Reminder with id [${id}] does not exist.`, null, $e);
                default:
                    throw new Exception('Something went wrong, please try later again.', null, $e);
            }
        }
    }

    /**
     * @param AmazonCommandRequest $request
     * @return mixed
     * @throws AlexaApiException
     */
    public function getAllReminders(AmazonCommandRequest $request) {
        try {
            return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, '/v1/alerts/reminders');
        } catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 401:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::alerts:reminders:skill:read] permissions.', null, $e);
                default:
                    throw new Exception('Something went wrong, please try later again.', null, $e);
            }
        }
    }

    /**
     * @param AmazonCommandRequest $request
     * @param array $payload
     * @return mixed
     * @throws InsufficientPermissionsGrantedException
     * @throws InvalidRequestException
     */
    public function createReminder(AmazonCommandRequest $request, array $payload) {
        try {
            return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_POST, '/v1/alerts/reminders', [], [], $payload);
        } catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 400:
                    throw new InvalidRequestException('Request body [' . print_r($payload, true) . '] is invalid.', null, $e);
                case 401:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::alerts:reminders:skill:write] permissions.', null, $e);
                default:
                    throw new Exception('Something went wrong, please try later again.', null, $e);
            }
        }
    }

    /**
     * @param AmazonCommandRequest $request
     * @param string $id
     * @param array $payload
     * @return mixed
     * @throws DataItemNotFoundException
     * @throws InsufficientPermissionsGrantedException
     * @throws InvalidRequestException
     */
    public function updateReminder(AmazonCommandRequest $request, string $id, array $payload) {
        try {
            return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_PUT, '/v1/alerts/reminders/' . $id, [], [], $payload);
        } catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 400:
                    throw new InvalidRequestException('Request body [' . print_r($payload, true) . '] is invalid.', null, $e);
                case 401:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::alerts:reminders:skill:write] permissions.', null, $e);
                case 404:
                    throw new DataItemNotFoundException(`Reminder with id [${id}] does not exist.`, null, $e);
                default:
                    throw new Exception('Something went wrong, please try later again.', null, $e);
            }
        }
    }

    /**
     * @param AmazonCommandRequest $request
     * @param string $id
     * @return mixed
     * @throws DataItemNotFoundException
     * @throws InsufficientPermissionsGrantedException
     */
    public function deleteReminder(AmazonCommandRequest $request, string $id) {
        try {
            return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_DELETE, '/v1/alerts/reminders/' . $id);
        } catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 401:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::alerts:reminders:skill:write] permissions.', null, $e);
                case 404:
                    throw new DataItemNotFoundException(`Reminder with id [${id}] does not exist.`, null, $e);
                default:
                    throw new Exception('Something went wrong, please try later again.', null, $e);
            }
        }
    }
}
