<?php declare(strict_types=1);

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Util\Test\ConvoTestCase;

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
     * @dataProvider getLaunchRequestWithAplInterface
     * @param $getLaunchRequestWithAplInterface
     * @throws Exception
     */
    public function testIsAplInterfaceEnabled($getLaunchRequestWithAplInterface)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getLaunchRequestWithAplInterface);
        $amazonCommandRequest->init();
        $this->assertEquals(true, $amazonCommandRequest->getIsAplSupported());
    }

    /**
     * @dataProvider getLaunchRequestWithoutViewport
     * @param $getLaunchRequestWithViewport
     * @throws Exception
     */
    public function testIsWithoutDisplayRequest($getLaunchRequestWithViewport)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getLaunchRequestWithViewport);
        $amazonCommandRequest->init();
        $this->assertEquals(false, $amazonCommandRequest->getIsDisplaySupported());
    }

    /**
     * @dataProvider getRegularNextRequest
     * @param $getRegularNextRequest
     * @throws Exception
     */
    public function testRegularNextRequest($getRegularNextRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getRegularNextRequest);
        $amazonCommandRequest->init();
        $this->assertEquals(false, $amazonCommandRequest->isMediaRequest());
    }

    /**
     * @dataProvider getNextMediaRequest
     * @param $getNextMediaRequest
     * @throws Exception
     */
    public function testNextMediaRequest($getNextMediaRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getNextMediaRequest);
        $amazonCommandRequest->init();
        $this->assertEquals(true, $amazonCommandRequest->isMediaRequest());
    }

    /**
     * @dataProvider getRegularPreviousRequest
     * @param $getRegularPreviousRequest
     * @throws Exception
     */
    public function testRegularPreviousRequest($getRegularPreviousRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getRegularPreviousRequest);
        $amazonCommandRequest->init();
        $this->assertEquals(false, $amazonCommandRequest->isMediaRequest());
    }

    /**
     * @dataProvider getPreviousMediaRequest
     * @param $getPreviousMediaRequest
     * @throws Exception
     */
    public function testPreviousMediaRequest($getPreviousMediaRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getPreviousMediaRequest);
        $amazonCommandRequest->init();
        $this->assertEquals(true, $amazonCommandRequest->isMediaRequest());
    }

    /**
     * @dataProvider getRegularRepeatRequest
     * @param $getRegularRepeatRequest
     * @throws Exception
     */
    public function testRegularRepeatRequest($getRegularRepeatRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getRegularRepeatRequest);
        $amazonCommandRequest->init();
        $this->assertEquals(false, $amazonCommandRequest->isMediaRequest());
    }

    /**
     * @dataProvider getRepeatMediaRequest
     * @param $getRepeatMediaRequest
     * @throws Exception
     */
    public function testRepeatMediaRequest($getRepeatMediaRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getRepeatMediaRequest);
        $amazonCommandRequest->init();
        $this->assertEquals(true, $amazonCommandRequest->isMediaRequest());
    }

    // data providers form real json requests
    public function getLaunchRequestWithViewport() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_with_viewport.json');
    }

    public function getLaunchRequestWithoutViewport() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_without_viewport.json');
    }

    public function getLaunchRequestWithAplInterface() {
        return $this->_establishTestData(__DIR__ . './data/launch_request_with_display_interface.json');
    }

    public function getRegularNextRequest() {
        return $this->_establishTestData(__DIR__ . './data/next_intent_request.json');
    }

    public function getNextMediaRequest() {
        return $this->_establishTestData(__DIR__ . './data/next_intent_request_after_play_directive.json');
    }

    public function getRegularPreviousRequest() {
        return $this->_establishTestData(__DIR__ . './data/previous_intent_request.json');
    }

    public function getPreviousMediaRequest() {
        return $this->_establishTestData(__DIR__ . './data/previous_intent_request_after_play_directive.json');
    }

    public function getRegularRepeatRequest() {
        return $this->_establishTestData(__DIR__ . './data/repeat_intent_request.json');
    }

    public function getRepeatMediaRequest() {
        return $this->_establishTestData(__DIR__ . './data/repeat_intent_request_after_play_directive.json');
    }
}
