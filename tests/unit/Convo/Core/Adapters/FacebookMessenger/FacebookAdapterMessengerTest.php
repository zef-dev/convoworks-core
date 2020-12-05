<?php

use Convo\Core\Adapters\Fbm\FacebookMessengerCommandRequest;
use Convo\Core\Util\Test\ConvoTestCase;

class FacebookAdapterMessengerTest extends ConvoTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider getMessengerLaunchRequestProvider
     * @param $getMessengerLaunchRequestProvider
     * @throws Exception
     */
    public function testRequestSession($getMessengerLaunchRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerLaunchRequestProvider);
        foreach ($facebookMessengerCommandRequest->getPlatformData()['entry'] as $entry) {
            $facebookMessengerCommandRequest->setEntry($entry);
            $facebookMessengerCommandRequest->init();

            $this->assertEquals(true, $facebookMessengerCommandRequest->isLaunchRequest());
            $this->assertEquals($entry["messaging"][0]["sender"]["id"], $facebookMessengerCommandRequest->getSessionId());
        }
    }

    /**
     * @dataProvider getMessengerLaunchRequestProvider
     * @param $getMessengerLaunchRequestProvider
     * @throws Exception
     */
    public function testLaunchRequest($getMessengerLaunchRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerLaunchRequestProvider);
        foreach ($facebookMessengerCommandRequest->getPlatformData()['entry'] as $entry) {
            $facebookMessengerCommandRequest->setEntry($entry);
            $facebookMessengerCommandRequest->init();

            $this->assertEquals(true, $facebookMessengerCommandRequest->isLaunchRequest());
        }
    }

    /**
     * @dataProvider getMessengerPostbackRequestProvider
     * @param $getMessengerPostbackRequestProvider
     * @throws Exception
     */
    public function testPostbackRequest($getMessengerPostbackRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerPostbackRequestProvider);
        foreach ($facebookMessengerCommandRequest->getPlatformData()['entry'] as $entry) {
            $facebookMessengerCommandRequest->setEntry($entry);
            $facebookMessengerCommandRequest->init();

            $this->assertEquals(false, $facebookMessengerCommandRequest->isLaunchRequest());
        }
    }

    /**
     * @dataProvider getMessengerTextRequestProvider
     * @param $getMessengerTextRequestProvider
     * @throws Exception
     */
    public function testTextRequest($getMessengerTextRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerTextRequestProvider);
        foreach ($facebookMessengerCommandRequest->getPlatformData()['entry'] as $entry) {
            $facebookMessengerCommandRequest->setEntry($entry);
            $facebookMessengerCommandRequest->init();

            $this->assertEquals(false, $facebookMessengerCommandRequest->isLaunchRequest());
            $this->assertEquals("Hi", $facebookMessengerCommandRequest->getText());
        }
    }

    /**
     * @dataProvider getMessengerAttachmentsRequestProvider
     * @param $getMessengerAttachmentsRequestProvider
     * @throws Exception
     */
    public function testAttachmentsRequest($getMessengerAttachmentsRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerAttachmentsRequestProvider);
        foreach ($facebookMessengerCommandRequest->getPlatformData()['entry'] as $entry) {
            $facebookMessengerCommandRequest->setEntry($entry);
            $facebookMessengerCommandRequest->init();

            $this->assertEquals(false, $facebookMessengerCommandRequest->isLaunchRequest());
        }
    }

    public function getMessengerLaunchRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/messenger_launch_request.json');
    }

    public function getMessengerTextRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/messenger_text_command_request.json');
    }

    public function getMessengerAttachmentsRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/messenger_attachments_command_request.json');
    }

    public function getMessengerPostbackRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/messenger_postback_command_request.json');
    }
}
