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
     * @dataProvider getContinuePlaybackRequest
     * @param $getContinuePlaybackRequest
     * @throws Exception
     */
    public function testContinuePlaybackRequest($getContinuePlaybackRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getContinuePlaybackRequest);
        $amazonCommandRequest->init();
        $this->assertEquals(true, $amazonCommandRequest->isMediaRequest());
    }

    /**
     * @dataProvider getContinuePlaybackAfterPlayDirectiveRequest
     * @param $getContinuePlaybackAfterPlayDirectiveRequest
     * @throws Exception
     */
    public function testContinuePlaybackAfterPlayDirectiveRequest($getContinuePlaybackAfterPlayDirectiveRequest)
    {
        $amazonCommandRequest = new AmazonCommandRequest($this->_logger, self::SERVICE_ID, $getContinuePlaybackAfterPlayDirectiveRequest);
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

    public function getLaunchRequestWithDisplayInterface() {
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

    public function getContinuePlaybackRequest() {
        return $this->_establishTestData(__DIR__ . './data/continue_playback_intent.json');
    }

    public function getContinuePlaybackAfterPlayDirectiveRequest() {
        return $this->_establishTestData(__DIR__ . './data/continue_playback_intent_after_play_directive.json');
    }

}
