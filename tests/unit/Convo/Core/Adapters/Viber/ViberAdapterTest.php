<?php

use Convo\Core\Adapters\Viber\ViberCommandRequest;
use Convo\Core\Adapters\Viber\ViberCommandResponse;
use Convo\Core\Util\Test\ConvoTestCase;

class ViberAdapterTest extends ConvoTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider getViberWebhookSetupRequestProvider
     * @param $getViberWebhookSetupRequestProvider
     * @throws Exception
     */
    public function testViberWebhookSetup($getViberWebhookSetupRequestProvider) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger, "Test", $getViberWebhookSetupRequestProvider);
        $viberCommandRequest->init();

        $this->assertEquals(true, $viberCommandRequest->isWebhookRequest());
    }

    /**
     * @dataProvider getViberLaunchRequestProvider
     * @param $getViberLaunchRequestProvider
     * @throws Exception
     */
    public function testViberLaunchRequest($getViberLaunchRequestProvider) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger, "test", $getViberLaunchRequestProvider);
        $viberCommandRequest->init();

        $this->assertEquals(true, $viberCommandRequest->isLaunchRequest());
    }

    /**
     * @dataProvider getViberTextMessageRequestProvider
     * @param $getViberTextMessageRequestProvider
     * @throws Exception
     */
    public function testTextMessage($getViberTextMessageRequestProvider) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger, "Test", $getViberTextMessageRequestProvider);
        $viberCommandRequest->init();

        $viberCommandResponse = new ViberCommandResponse();
        $viberCommandResponse->setSenderName($viberCommandRequest->getServiceId());
        $viberCommandResponse->setReceiver($viberCommandRequest->getSessionId());
        $viberCommandResponse->setText($viberCommandRequest->getText());

        $this->assertEquals('Hi', $viberCommandRequest->getText());

        $data = $viberCommandResponse->getPlatformResponse();

        $this->assertEquals($data['receiver'], $viberCommandRequest->getSessionId());
        $this->assertEquals($data['sender']['name'], $viberCommandRequest->getServiceId());
    }

    /**
     * @dataProvider getViberTextMessageRequestProvider
     * @param $getViberTextMessageRequestProvider
     * @throws Exception
     */
    public function testInstallationIdAndSessionIdAndRequestId($getViberTextMessageRequestProvider) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger, "test", $getViberTextMessageRequestProvider);
        $viberCommandRequest->init();

        $this->assertEquals('9nCr2GSFtI7s+DtDie23/Q==', $viberCommandRequest->getSessionId());
        $this->assertEquals('test_9nCr2GSFtI7s+DtDie23/Q==', $viberCommandRequest->getInstallationId());
        $this->assertEquals(5469165883537819000, $viberCommandRequest->getRequestId());
    }

    public function getViberWebhookSetupRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/viber_webhook_setup.json');
    }

    public function getViberLaunchRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/viber_session_start.json');
    }

    public function getViberTextMessageRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/viber_text_message_request.json');
    }
}
