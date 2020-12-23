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

        $viberCommandResponse = new ViberCommandResponse($viberCommandRequest);
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

    /**
     * @dataProvider getViberListItemTextMessageRequestProvider
     * @param $getViberListItemTextMessageRequestProvider
     * @throws Exception
     */
    public function testListItemTextMessage($getViberListItemTextMessageRequestProvider) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger, "Test", $getViberListItemTextMessageRequestProvider);
        $viberCommandRequest->init();

        $viberCommandResponse = new ViberCommandResponse($viberCommandRequest);
        $viberCommandResponse->setSenderName($viberCommandRequest->getServiceId());
        $viberCommandResponse->setReceiver($viberCommandRequest->getSessionId());
        $viberCommandResponse->setText($viberCommandRequest->getText());

        $this->assertEquals(0, $viberCommandRequest->getSelectedItemIndex());
    }

    /**
     * @dataProvider getViberCardActionTextMessageRequestProvider
     * @param $getViberCardActionTextMessageRequestProvider
     * @throws Exception
     */
    public function testCardActionTextMessage($getViberCardActionTextMessageRequestProvider) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger, "Test", $getViberCardActionTextMessageRequestProvider);
        $viberCommandRequest->init();

        $viberCommandResponse = new ViberCommandResponse($viberCommandRequest);
        $viberCommandResponse->setSenderName($viberCommandRequest->getServiceId());
        $viberCommandResponse->setReceiver($viberCommandRequest->getSessionId());
        $viberCommandResponse->setText($viberCommandRequest->getText());

        $this->assertEquals("show_more", $viberCommandRequest->getSelectedCardAction());
    }

    /**
     * @dataProvider getViberTextMessageRequestProvider
     * @param $getViberTextMessageRequestProvider
     * @throws Exception
     */
    public function testListResponseAsKeyboardWithoutImages($getViberTextMessageRequestProvider) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger, "Test", $getViberTextMessageRequestProvider);
        $viberCommandRequest->init();

        $viberCommandResponse = new ViberCommandResponse($viberCommandRequest);
        $viberCommandResponse->setSenderName($viberCommandRequest->getServiceId());
        $viberCommandResponse->setReceiver($viberCommandRequest->getSessionId());
        $viberCommandResponse->setText($viberCommandRequest->getText());

        $listItems[] = new \Convo\Core\Workflow\VisualItem("List Item 1", "List Subtitle 1", "Description of List Item 1", "", "");
        $listItems[] = new \Convo\Core\Workflow\VisualItem("List Item 2", "List Subtitle 2", "Description of List Item 2", "", "");
        $listItems[] = new \Convo\Core\Workflow\VisualItem("List Item 3", "List Subtitle 3", "Description of List Item 3", "", "");

        $listDefinition = new \Convo\Core\Workflow\VisualList("List Title", "LIST", $listItems);

        $viberCommandResponse->getListResponse($listDefinition);
        $listResponse = $viberCommandResponse->getPlatformResponse();

        $this->_logger->info(print_r($listResponse, true));

        $this->assertTrue(isset($listResponse['keyboard']));
        $this->assertCount(3, $listResponse['keyboard']['Buttons']);
    }

    /**
     * @dataProvider getViberTextMessageRequestProvider
     * @param $getViberTextMessageRequestProvider
     * @throws Exception
     */
    public function testCardResponse($getViberTextMessageRequestProvider) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger, "Test", $getViberTextMessageRequestProvider);
        $viberCommandRequest->init();

        $viberCommandResponse = new ViberCommandResponse($viberCommandRequest);
        $viberCommandResponse->setSenderName($viberCommandRequest->getServiceId());
        $viberCommandResponse->setReceiver($viberCommandRequest->getSessionId());
        $viberCommandResponse->setText($viberCommandRequest->getText());

        $cardActions[] = new \Convo\Core\Workflow\CardAction("card_action_buy", "Buy");
        $cardActions[] = new \Convo\Core\Workflow\CardAction("card_action_show_more", "Show More");
        $cardActions[] = new \Convo\Core\Workflow\CardAction("card_action_call", "Call");
        $cardActions[] = new \Convo\Core\Workflow\CardAction("card_action_test", "Test");

        $card = new \Convo\Core\Workflow\VisualItem("Title", "Subtitle", "Description", "", "");

        $cardDefinition = new \Convo\Core\Workflow\VisualCard($card, $cardActions);

        $viberCommandResponse->getCardResponse($cardDefinition);
        $listResponse = $viberCommandResponse->getPlatformResponse();

        $this->_logger->info(print_r($listResponse, true));

        $this->assertTrue(isset($listResponse['rich_media']));
        $this->assertCount(5, $listResponse['rich_media']['Buttons']);
    }

    /**
     * @dataProvider getViberTextMessageRequestProvider
     * @param $getViberTextMessageRequestProvider
     * @throws Exception
     */
    public function testListResponseAsKeyboardWithImages($getViberTextMessageRequestProvider) {
        $viberCommandRequest = new ViberCommandRequest($this->_logger, "Test", $getViberTextMessageRequestProvider);
        $viberCommandRequest->init();

        $viberCommandResponse = new ViberCommandResponse($viberCommandRequest);
        $viberCommandResponse->setSenderName($viberCommandRequest->getServiceId());
        $viberCommandResponse->setReceiver($viberCommandRequest->getSessionId());
        $viberCommandResponse->setText($viberCommandRequest->getText());

        $listItems[] = new \Convo\Core\Workflow\VisualItem("List Item 1", "List Subtitle 1", "Description of List Item 1", "https://zef.dev/images/zef-logo-light.png", "ZEF DEV");
        $listItems[] = new \Convo\Core\Workflow\VisualItem("List Item 2", "List Subtitle 2", "Description of List Item 2", "https://zef.dev/images/zef-logo-light.png", "ZEF DEV");
        $listItems[] = new \Convo\Core\Workflow\VisualItem("List Item 3", "List Subtitle 3", "Description of List Item 3", "https://zef.dev/images/zef-logo-light.png", "ZEF DEV");

        $listDefinition = new \Convo\Core\Workflow\VisualList("List Title", "LIST", $listItems);

        $viberCommandResponse->getListResponse($listDefinition);
        $listResponse = $viberCommandResponse->getPlatformResponse();

        $this->_logger->info(print_r($listResponse, true));

        $this->assertTrue(isset($listResponse['keyboard']));
        $this->assertCount(6, $listResponse['keyboard']['Buttons']);
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

    public function getViberListItemTextMessageRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/viber_text_message_list_item_request.json');
    }

    public function getViberCardActionTextMessageRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/viber_text_message_card_action_request.json');
    }
}
