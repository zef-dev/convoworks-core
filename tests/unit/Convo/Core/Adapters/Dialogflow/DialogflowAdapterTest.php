<?php declare(strict_types=1);

use Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandRequest;
use Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandResponse;
use Convo\Core\Util\Test\ConvoTestCase;

class DialogflowAdapterTest extends ConvoTestCase
{
    private const SERVICE_ID = 'my-soccer-man';

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider getMatchesRequestProvider
     * @param $getMatchesRequest
     * @throws Exception
     */
    public function testSlotValueWithTeamName($getMatchesRequest)
    {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getMatchesRequest);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $slotValues = $dialogflowCommandRequest->getSlotValues();

        $this->assertEquals(false, $isHealthCheck);
        $this->assertCount(1, $slotValues);
        $this->assertEquals(false, $dialogflowCommandRequest->isLaunchRequest());
        $this->assertArrayHasKey('TeamName', $slotValues, 'Slot TeamName is present.');
        $this->assertEquals(59, $slotValues['TeamName'], 'Value of slot is not correct.');
    }

    /**
     * @dataProvider getMatchesRequestProvider
     * @param $getMatchesRequest
     * @throws Exception
     */
    public function testIntentName($getMatchesRequest) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getMatchesRequest);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $intentName = $dialogflowCommandRequest->getIntentName();

        $this->assertEquals(false, $isHealthCheck);
        $this->assertEquals(false, $dialogflowCommandRequest->isLaunchRequest());
        $this->assertEquals('Matches', $intentName, 'Intent name is not correct.');
    }

    /**
     * @dataProvider getLaunchRequestProvider
     * @param $getLaunchRequest
     * @throws Exception
     */
    public function testLaunchRequest($getLaunchRequest) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getLaunchRequest);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $this->assertEquals(false, $isHealthCheck);
        $this->assertEquals(true, $dialogflowCommandRequest->isLaunchRequest(), 'This is not a launch request.');
    }

    /**
     * @dataProvider getLaunchRequestWithTriggerQueryProvider
     * @param $getLaunchRequestWithTriggerQuery
     * @throws Exception
     */
    public function testLaunchRequestWithTriggerQuery($getLaunchRequestWithTriggerQuery) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getLaunchRequestWithTriggerQuery);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $intentName = $dialogflowCommandRequest->getIntentName();

        $this->assertEquals(false, $isHealthCheck);
        $this->assertEquals(true, $dialogflowCommandRequest->isLaunchRequest(), 'This is not a launch request.');
        $this->assertEquals("Matches", $intentName, 'The intent name is not correct.');
    }

    /**
     * @dataProvider getRegularExitRequestProvider
     * @param $getRegularExitRequest
     * @throws Exception
     */
    public function testRegularExit($getRegularExitRequest) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getRegularExitRequest);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $intentName = $dialogflowCommandRequest->getIntentName();
        $text = $dialogflowCommandRequest->getText();

        $this->assertEquals(false, $isHealthCheck);
        $this->assertEquals(false, $dialogflowCommandRequest->isLaunchRequest(), 'This should not be a launch request.');
        $this->assertEquals('actions.intent.CANCEL', $intentName, 'Exit event is not correct.');
        $this->assertEquals('exit', $text, 'Exit text is not correct.');
    }

    /**
     * @dataProvider getAssistantCancelRequestProvider
     * @param $getAssistantCancelRequestProvider
     * @throws Exception
     */
    public function testAssistantCancel($getAssistantCancelRequestProvider) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getAssistantCancelRequestProvider);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $intentName = $dialogflowCommandRequest->getIntentName();
        $text = $dialogflowCommandRequest->getText();

        $this->assertEquals(false, $isHealthCheck);
        $this->assertEquals(false, $dialogflowCommandRequest->isLaunchRequest(), 'This should not be a launch request.');
        $this->assertEquals('ASSISTANT_CANCEL', $intentName, 'Exit event is not correct.');
        $this->assertEquals('exit', $text, 'Exit text is not correct.');
    }

    /**
     * @dataProvider getSpecialExitRequestProvider
     * @param $getSpecialExitRequest
     * @throws Exception
     */
    public function testSpecialExit($getSpecialExitRequest) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getSpecialExitRequest);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $intentName = $dialogflowCommandRequest->getIntentName();
        $text = $dialogflowCommandRequest->getText();

        $this->assertEquals(false, $isHealthCheck);
        $this->assertEquals('exit', $text, 'Exit text is not correct.');
        $this->assertEquals('SpecialExit', $intentName, 'Exit intent is not correct.');
    }

    /**
     * @dataProvider getOptionSelectedRequestProvider
     * @param $getOptionSelectedRequest
     * @throws Exception
     */
    public function testItemSelected($getOptionSelectedRequest) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getOptionSelectedRequest);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $intentName = $dialogflowCommandRequest->getIntentName();
        $selectedOption = $dialogflowCommandRequest->getSelectedOption();

        $this->assertEquals(false, $isHealthCheck);
        $this->assertEquals(false, $dialogflowCommandRequest->isLaunchRequest(), 'This should not be a launch request.');
        $this->assertEquals('0', $selectedOption, 'Selected option is not correct.');
        $this->assertEquals('actions.intent.OPTION', $intentName, 'Option intent is not correct.');
    }
    /**
     * @dataProvider getDetectIntentRequest
     */
    public function testRequestWithoutConversation($getDetectIntentRequest) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getDetectIntentRequest);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $sessionId = $dialogflowCommandRequest->getSessionId();
        $this->_logger->info('Using uuidV4 as conversationId ['. $sessionId . ']');
        $this->assertEquals(false, $isHealthCheck);
        $this->assertNotEmpty($sessionId, 'Conversation ID is still missing.');
    }

    /**
     * @dataProvider getLaunchRequestWithoutUserStorage
     * @param $getLaunchRequestWithoutUserStorage
     * @throws Exception
     */
    public function testRequestWithoutUserStorage($getLaunchRequestWithoutUserStorage) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getLaunchRequestWithoutUserStorage);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $preparedInstallationId = $dialogflowCommandRequest->getPreparedInstallationId();
        $installationId = $dialogflowCommandRequest->getInstallationId();
        $this->_logger->info('Using uuidV4 as installationId ['. $preparedInstallationId . ']');
        $this->assertEquals(false, $isHealthCheck);
        $this->assertNotEmpty($preparedInstallationId, 'Prepared installation ID is still missing.');
        $this->assertNotEmpty($installationId, 'Installation ID is still missing.');
        $this->assertEquals($preparedInstallationId, $installationId);
    }

    /**
     * @dataProvider getLaunchRequestWithUserStorage
     * @param $getLaunchRequestWithUserStorage
     * @throws Exception
     */
    public function testRequestWithUserStorage($getLaunchRequestWithUserStorage) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getLaunchRequestWithUserStorage);
        $dialogflowCommandRequest->init();

        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $preparedInstallationId = $dialogflowCommandRequest->getPreparedInstallationId();
        $installationId = $dialogflowCommandRequest->getInstallationId();

        $this->_logger->info('Using installationId from user storage ['. $installationId . ']');
        $this->assertEquals(false, $isHealthCheck);
        $this->assertNotEmpty($installationId, 'Installation ID is still missing from user storage.');
    }

    /**
     * @dataProvider getLaunchRequestWithoutUserStorage
     * @param $getLaunchRequestWithoutUserStorage
     * @throws Exception
     */
    public function testResponseWhichAddsUserStorage($getLaunchRequestWithoutUserStorage) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getLaunchRequestWithoutUserStorage);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();

        $dialogflowCommandResponse = new DialogflowCommandResponse([], $dialogflowCommandRequest);
        $dialogFlowAppResponse = json_decode($dialogflowCommandResponse->getPlatformResponse(), true);
        $this->_logger->info(print_r($dialogFlowAppResponse, true));

        $this->assertEquals(false, $isHealthCheck);

        $this->assertNotEmpty($dialogFlowAppResponse['payload']['google']['userStorage']);

        $installationId = $dialogFlowAppResponse['payload']['google']['userStorage'];
        $installationId = stripslashes($installationId);
        $installationId = json_decode($installationId, true)['data']['installationId'];

        $this->_logger->info('InstallationId from user storage ['. $installationId . ']');

        $this->assertEquals($dialogflowCommandRequest->getInstallationId(), $installationId);
    }

    /**
     * @dataProvider getLaunchRequestWithUserStorage
     * @param $getLaunchRequestWithUserStorage
     * @throws Exception
     */
    public function testResponseWhichOverridesExistingUserStorage($getLaunchRequestWithUserStorage) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getLaunchRequestWithUserStorage);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $dialogflowCommandResponse = new DialogflowCommandResponse([], $dialogflowCommandRequest);
        $dialogFlowAppResponse = json_decode($dialogflowCommandResponse->getPlatformResponse(), true);

        $this->assertEquals(false, $isHealthCheck);
        $this->assertArrayHasKey('userStorage', $dialogFlowAppResponse['payload']['google']);
        $this->assertEquals("1b6cd3a2-c370-4983-9d13-1c5d3d0f4c7c", $dialogflowCommandRequest->getInstallationId());
    }

    /**
     * @dataProvider getLaunchRequestWithGuestUser
     * @param $getLaunchRequestWithGuestUser
     * @throws Exception
     */
    public function testRequestWithGuestUser($getLaunchRequestWithGuestUser) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getLaunchRequestWithGuestUser);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $sessionId = $dialogflowCommandRequest->getSessionId();
        $installationId = $dialogflowCommandRequest->getInstallationId();
        $this->_logger->info('Session id ['. $sessionId . ']');
        $this->_logger->info('Installation id ['. $installationId . ']');
        $this->assertEquals(false, $isHealthCheck);
        $this->assertNotEmpty($sessionId, 'Prepared installation ID is still missing.');
        $this->assertNotEmpty($installationId, 'Installation ID is still missing.');
        $this->assertEquals($sessionId, $installationId);
    }

    /**
     * @dataProvider getLaunchRequestWithoutUserVerificationStatus
     * @param $getLaunchRequestWithoutUserVerificationStatus
     * @throws Exception
     */
    public function testRequestWithoutUserVerificationStatus($getLaunchRequestWithoutUserVerificationStatus) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getLaunchRequestWithoutUserVerificationStatus);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();
        $sessionId = $dialogflowCommandRequest->getSessionId();
        $installationId = $dialogflowCommandRequest->getInstallationId();
        $this->_logger->info('Session id ['. $sessionId . ']');
        $this->_logger->info('Installation id ['. $installationId . ']');
        $this->assertEquals(false, $isHealthCheck);
        $this->assertNotEmpty($sessionId, 'Prepared installation ID is still missing.');
        $this->assertNotEmpty($installationId, 'Installation ID is still missing.');
        $this->assertEquals($sessionId, $installationId);
    }

    /**
     * @dataProvider getOptionSelectedWithHealthCheckRequestProvider
     * @param $getOptionSelectedWithHealthCheckRequestProvider
     * @throws Exception
     */
    public function testItemSelectedWithHealthCheck($getOptionSelectedWithHealthCheckRequestProvider) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getOptionSelectedWithHealthCheckRequestProvider);
        $dialogflowCommandRequest->init();
        $isHealthCheck = $dialogflowCommandRequest->isHealthCheck();

        $this->assertEquals(true, $isHealthCheck, 'This should be an health check.');
    }

    /**
     * @dataProvider getZeroAsTextRequestProvider
     * @param $getZeroAsTextRequestProvider
     * @throws Exception
     */
    public function testZeroAsText($getZeroAsTextRequestProvider) {
        $dialogflowCommandRequest = new DialogflowCommandRequest(self::SERVICE_ID, $getZeroAsTextRequestProvider);
        $dialogflowCommandRequest->init();
        $isRequestEmpty = $dialogflowCommandRequest->isEmpty();
        $this->_logger->info("Text to get [" . $dialogflowCommandRequest->getText() . "]");
        $this->assertEquals(false, $isRequestEmpty, 'This should be an health check.');    }

    // data providers form real json requests
    public function getMatchesRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/get_matches_request.json');
    }

    public function getLaunchRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/launch_request.json');
    }

    public function getLaunchRequestWithTriggerQueryProvider() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_with_trigger_query.json');
    }

    public function getRegularExitRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/regular_exit_request.json');
    }

    public function getAssistantCancelRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/assistant_cancel_request.json');
    }

    public function getSpecialExitRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/special_exit_request.json');
    }

    public function getOptionSelectedRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/item_selected_request.json');
    }

    public function getDetectIntentRequest() {
        return $this->_establishTestData(__DIR__ . './data/detect_intent_request.json');
    }

    public function getLaunchRequestWithoutUserStorage() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_without_user_storage.json');
    }

    public function getLaunchRequestWithUserStorage() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_with_user_storage.json');
    }

    public function getLaunchRequestWithGuestUser() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_with_guest_user.json');
    }

    public function getLaunchRequestWithoutUserVerificationStatus() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_without_user_verification_status.json');
    }

    public function getOptionSelectedWithHealthCheckRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/item_selected_with_health_check_request.json');
    }

    public function getZeroAsTextRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/request_with_zero_as_text.json');
    }
}
