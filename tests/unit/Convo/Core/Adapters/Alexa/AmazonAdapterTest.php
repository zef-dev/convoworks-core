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
}
