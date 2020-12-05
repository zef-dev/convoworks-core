<?php

namespace Convo\Core\Util\Test;

use Convo\Core\Util\EchoLogger;
use PHPUnit\Framework\TestCase;

class ConvoTestCase extends TestCase
{
    /**
     * @var EchoLogger
     */
    protected $_logger;

    public function setUp(): void
    {
        $this->_logger  =   new EchoLogger();
    }

    /**
     * @param $filePath
     * @return array[]
     */
    protected function _establishTestData($filePath) {
        $fileContents = file_get_contents($filePath);
        return [
            [
                json_decode($fileContents, true)
            ]
        ];
    }

    public function testExample()
    {
        $this->assertEquals(1, 1);
    }
}
