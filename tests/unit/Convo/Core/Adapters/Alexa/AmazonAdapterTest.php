<?php declare(strict_types=1);

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Alexa\AmazonCommandResponse;
use Convo\Core\Util\Test\ConvoTestCase;
use Convo\Core\Workflow\VisualCard;
use Convo\Core\Workflow\VisualList;

class AmazonAdapterTest extends ConvoTestCase
{
    private const SERVICE_ID = 'my-soccer-man';

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider getLaunchRequestWithViewport
     * @param $getLaunchRequestWithViewport
     * @throws Exception
     */
    public function testIsDisplaySupported($getLaunchRequestWithViewport)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getLaunchRequestWithViewport);
        $amazonCommandRequest->init();
        $this->assertEquals(true, $amazonCommandRequest->getIsDisplaySupported());
    }

    /**
     * @dataProvider getLaunchRequestWithoutViewport
     * @param $getLaunchRequestWithoutViewport
     * @throws Exception
     */
    public function testIsDisplayNotSupported($getLaunchRequestWithoutViewport)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getLaunchRequestWithoutViewport);
        $amazonCommandRequest->init();
        $this->assertEquals(false, $amazonCommandRequest->getIsDisplaySupported());
    }

    /**
     * @dataProvider getLaunchRequestWithDisplayInterface
     * @param $getLaunchRequestWithDisplayInterface
     * @throws Exception
     */
    public function testIsDisplayInterfaceEnabled($getLaunchRequestWithDisplayInterface)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getLaunchRequestWithDisplayInterface);
        $amazonCommandRequest->init();
        $this->assertEquals(true, $amazonCommandRequest->getIsDisplayInterfaceEnabled());
    }

    /**
     * @dataProvider getLaunchRequestWithoutViewport
     * @param $getLaunchRequestWithViewport
     * @throws Exception
     */
    public function testIsDisplayInterfaceNotEnabled($getLaunchRequestWithViewport)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getLaunchRequestWithViewport);
        $amazonCommandRequest->init();
        $this->assertEquals(false, $amazonCommandRequest->getIsDisplayInterfaceEnabled());
    }

    /**
     * @dataProvider getDisplayElementListItemSelectedRequest
     * @param $getDisplayElementSelectedRequest
     * @throws Exception
     */
    public function testDisplayListItemSelection($getDisplayElementSelectedRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getDisplayElementSelectedRequest);
        $amazonCommandRequest->init();
        $this->_logger->debug("Extracted index [" . $amazonCommandRequest->getSelectedItemIndex() . "] from string.");
        $this->assertEquals(0, $amazonCommandRequest->getSelectedItemIndex());
    }

    /**
     * @dataProvider getDisplayElementCardActionSelectedRequest
     * @param $getDisplayElementCardActionSelectedRequest
     * @throws Exception
     */
    public function testDisplayCardItemSelection($getDisplayElementCardActionSelectedRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getDisplayElementCardActionSelectedRequest);
        $amazonCommandRequest->init();
        $this->_logger->debug("Extracted action name [" . $amazonCommandRequest->getSelectedCardAction() . "] from string.");
        $this->assertEquals("show_more", $amazonCommandRequest->getSelectedCardAction());
    }

    /**
     * @dataProvider getDisplayElementCardActionSelectedRequest
     * @param $getDisplayElementCardActionSelectedRequest
     * @throws Exception
     */
    public function testListResponse($getDisplayElementCardActionSelectedRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getDisplayElementCardActionSelectedRequest);
        $amazonCommandRequest->init();

        $visualItems[] = new \Convo\Core\Workflow\VisualItem("Item 1", "Item 1", "Desc of item 1", "", "");
        $visualItems[] = new \Convo\Core\Workflow\VisualItem("Item 2", "Item 2", "Desc of item 2", "", "");
        $visualItems[] = new \Convo\Core\Workflow\VisualItem("Item 3", "Item 3", "Desc of item 3", "", "");

        $listDefinition = new VisualList("Some title", "LIST", $visualItems);

        $amazonCommandResponse = new AmazonCommandResponse($amazonCommandRequest);
        $listResponse = $amazonCommandResponse->getListResponse($listDefinition);
        $this->_logger->debug("List response: [" . print_r($listResponse, true) . "]");
        $this->assertTrue(isset($listResponse['response']['directives'][0]));
        $this->assertEquals('Display.RenderTemplate', $listResponse['response']['directives'][0]['type']);
        $this->assertTrue(isset($listResponse['response']['directives'][0]['template']));
        $this->assertEquals('Some title', $listResponse['response']['directives'][0]['template']['title']);
        $this->assertEquals('ListTemplate1', $listResponse['response']['directives'][0]['template']['type']);
        $this->assertEquals('0', $listResponse['response']['directives'][0]['template']['token']);
        $this->assertEquals('HIDDEN', $listResponse['response']['directives'][0]['template']['backButton']);
        $this->assertCount(3, $listResponse['response']['directives'][0]['template']['listItems']);
    }

    /**
     * @dataProvider getDisplayElementCardActionSelectedRequest
     * @param $getDisplayElementCardActionSelectedRequest
     * @throws Exception
     */
    public function testCardResponse($getDisplayElementCardActionSelectedRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getDisplayElementCardActionSelectedRequest);
        $amazonCommandRequest->init();

        $visualItem = new \Convo\Core\Workflow\VisualItem("Item 1", "Item 1", "Desc of item 1", "", "");

        $cardActions[] = new \Convo\Core\Workflow\CardAction("card_action_show_more", "Show More");
        $cardActions[] = new \Convo\Core\Workflow\CardAction("card_action_buy", "Buy");

        $card = new VisualCard($visualItem, $cardActions);

        $amazonCommandResponse = new AmazonCommandResponse($amazonCommandRequest);
        $cardResponse = $amazonCommandResponse->getCardResponse($card);
        $this->_logger->debug("Card response: [" . print_r($amazonCommandResponse->getCardResponse($card), true) . "]");
        $this->assertTrue(isset($cardResponse['response']['directives'][0]));
        $this->assertEquals('Display.RenderTemplate', $cardResponse['response']['directives'][0]['type']);
        $this->assertTrue(isset($cardResponse['response']['directives'][0]['template']));
        $this->assertEquals('BodyTemplate2', $cardResponse['response']['directives'][0]['template']['type']);
        $this->assertEquals('Item 1', $cardResponse['response']['directives'][0]['template']['title']);
        $this->assertEquals('0', $cardResponse['response']['directives'][0]['template']['token']);
        $this->assertEquals(false, $cardResponse['response']['directives'][0]['template']['backButton']);
        $this->assertEquals('RichText', $cardResponse['response']['directives'][0]['template']['textContent']['primaryText']['type']);
        $this->assertEquals('Desc of item 1', $cardResponse['response']['directives'][0]['template']['textContent']['primaryText']['text']);
        $this->assertEquals('RichText', $cardResponse['response']['directives'][0]['template']['textContent']['secondaryText']['type']);
        $this->assertStringContainsString("<action value='card_action_show_more'>", $cardResponse['response']['directives'][0]['template']['textContent']['secondaryText']['text']);
        $this->assertStringContainsString("<action value='card_action_buy'>", $cardResponse['response']['directives'][0]['template']['textContent']['secondaryText']['text']);
    }

    // data providers form real json requests
    public function getLaunchRequestWithViewport() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_with_viewport.json');
    }

    public function getLaunchRequestWithoutViewport() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_without_viewport.json');
    }

    public function getLaunchRequestWithDisplayInterface() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_with_display_interface.json');
    }

    public function getDisplayElementListItemSelectedRequest() {
        return $this->_establishTestData(__DIR__ . './data/alexa_display_element_selection_list_item_request.json');
    }

    public function getDisplayElementCardActionSelectedRequest() {
        return $this->_establishTestData(__DIR__ . './data/alexa_display_element_selection_card_action_request.json');
    }
}
