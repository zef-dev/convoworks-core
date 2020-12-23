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

    /**
     * @dataProvider getMessengerPostbackListItemRequestProvider
     * @param $getMessengerPostbackListItemRequestProvider
     * @throws Exception
     */
    public function testPostbackListItemRequest($getMessengerPostbackListItemRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerPostbackListItemRequestProvider);
        foreach ($facebookMessengerCommandRequest->getPlatformData()['entry'] as $entry) {
            $facebookMessengerCommandRequest->setEntry($entry);
            $facebookMessengerCommandRequest->init();

            $this->assertEquals(0, $facebookMessengerCommandRequest->getSelectedItemIndex());
        }
    }

    /**
     * @dataProvider getMessengerPostbackCardActionRequestProvider
     * @param $getMessengerPostbackCardActionRequestProvider
     * @throws Exception
     */
    public function testPostbackCardActionRequest($getMessengerPostbackCardActionRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerPostbackCardActionRequestProvider);
        foreach ($facebookMessengerCommandRequest->getPlatformData()['entry'] as $entry) {
            $facebookMessengerCommandRequest->setEntry($entry);
            $facebookMessengerCommandRequest->init();

            $this->assertEquals("show_more", $facebookMessengerCommandRequest->getSelectedCardAction());
        }
    }

    /**
     * @dataProvider getMessengerTextRequestProvider
     * @param $getMessengerTextRequestProvider
     * @throws Exception
     */
    public function testTextResponse($getMessengerTextRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerTextRequestProvider);
        $facebookMessengerCommandResponse = new \Convo\Core\Adapters\Fbm\FacebookMessengerCommandResponse($facebookMessengerCommandRequest);
        $textResponse = $facebookMessengerCommandResponse->getPlatformResponse();

        $this->assertTrue(isset($textResponse['text']));
    }

    /**
     * @dataProvider getMessengerTextRequestProvider
     * @param $getMessengerTextRequestProvider
     * @throws Exception
     */
    public function testCardResponse($getMessengerTextRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerTextRequestProvider);
        $facebookMessengerCommandResponse = new \Convo\Core\Adapters\Fbm\FacebookMessengerCommandResponse($facebookMessengerCommandRequest);

        $visualItem = new \Convo\Core\Workflow\VisualItem("Card Title", "Card Subtitle", "Description of Card", "", "");

        $cardActions[] = new \Convo\Core\Workflow\CardAction("card_action_show_more", "Show More");
        $cardActions[] = new \Convo\Core\Workflow\CardAction("card_action_buy", "Buy");

        $cardDefinition = new \Convo\Core\Workflow\VisualCard($visualItem, $cardActions);

        $cardResponse = $facebookMessengerCommandResponse->getCardResponse($cardDefinition);

        $this->assertTrue(isset($cardResponse['attachment']));
        $this->assertEquals('template', $cardResponse['attachment']['type']);
        $this->assertEquals('generic', $cardResponse['attachment']['payload']['template_type']);
        $this->assertCount(1, $cardResponse['attachment']['payload']['elements']);
        $this->assertEquals('Card Title', $cardResponse['attachment']['payload']['elements'][0]['title']);
        $this->assertEquals('Card Subtitle', $cardResponse['attachment']['payload']['elements'][0]['subtitle']);
        $this->assertTrue(isset($cardResponse['attachment']['payload']['elements'][0]['buttons']));
        $this->assertCount(2, $cardResponse['attachment']['payload']['elements'][0]['buttons']);
        $this->assertEquals('card_action_show_more', $cardResponse['attachment']['payload']['elements'][0]['buttons'][0]['payload']);
        $this->assertEquals('card_action_buy', $cardResponse['attachment']['payload']['elements'][0]['buttons'][1]['payload']);
    }

    /**
     * @dataProvider getMessengerTextRequestProvider
     * @param $getMessengerTextRequestProvider
     * @throws Exception
     */
    public function testListResponse($getMessengerTextRequestProvider)
    {
        $facebookMessengerCommandRequest = new FacebookMessengerCommandRequest($this->_logger, "test", $getMessengerTextRequestProvider);
        $facebookMessengerCommandResponse = new \Convo\Core\Adapters\Fbm\FacebookMessengerCommandResponse($facebookMessengerCommandRequest);

        $listItems[] = new \Convo\Core\Workflow\VisualItem("List Item 1", "List Subtitle 1", "Description of List Item 1", "", "");
        $listItems[] = new \Convo\Core\Workflow\VisualItem("List Item 2", "List Subtitle 2", "Description of List Item 2", "", "");
        $listItems[] = new \Convo\Core\Workflow\VisualItem("List Item 3", "List Subtitle 3", "Description of List Item 3", "", "");

        $listDefinition = new \Convo\Core\Workflow\VisualList("List Title", "LIST", $listItems);

        $listResponse = $facebookMessengerCommandResponse->getListResponse($listDefinition);

        $this->assertTrue(isset($listResponse['attachment']));
        $this->assertEquals('template', $listResponse['attachment']['type']);
        $this->assertEquals('generic', $listResponse['attachment']['payload']['template_type']);
        $this->assertCount(3, $listResponse['attachment']['payload']['elements']);
        $this->assertEquals('List Item 1', $listResponse['attachment']['payload']['elements'][0]['title']);
        $this->assertEquals('List Subtitle 1', $listResponse['attachment']['payload']['elements'][0]['subtitle']);
        $this->assertTrue(isset($listResponse['attachment']['payload']['elements'][0]['buttons']));
        $this->assertCount(1, $listResponse['attachment']['payload']['elements'][0]['buttons']);
        $this->assertEquals('list_item_0', $listResponse['attachment']['payload']['elements'][0]['buttons'][0]['payload']);
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

    public function getMessengerPostbackListItemRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/messenger_postback_list_item_command_request.json');
    }
    public function getMessengerPostbackCardActionRequestProvider() {
        return $this->_establishTestData(__DIR__ . './data/messenger_postback_card_action_command_request.json');
    }
}
