<?php declare(strict_types=1);

namespace Convo\Core\Util;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GzipEncoderMiddleware implements MiddlewareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    const ALLOWED_MIME_TYPES = [
        "application/json",
        "application/json+ld",
        "application/xhtml+xml",
        "text/javascript",
        "text/plain",
        "text/html"
    ];

    public function __construct($logger)
    {
        $this->_logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!in_array($response->getHeaderLine('Content-Type'), self::ALLOWED_MIME_TYPES)) {
            $this->_logger->info('Will not encode non supported mime types');
            return $response;
        }

        $body = trim($response->getBody()->__toString());

        $body = $this->_toStream((string) gzencode($body, -1, FORCE_GZIP));

        $response = $response
            ->withBody($body)
            ->withHeader('Content-Encoding', 'gzip')
            ->withHeader('Content-Length', $body->getSize());

        return $response;
    }

    private function _toStream($string)
    {
        return new class ($this->_logger, $string) implements StreamInterface
        {
            /**
             * @var \Psr\Log\LoggerInterface
             */
            private $_logger;

            /**
             * @var resource
             */
            private $_resource;

            private $_size;

            public function __destruct()
            {
                $this->close();
            }

            public function __construct($logger, $string)
            {
                $this->_logger = $logger;

                if (($this->_resource = fopen('php://memory', 'a+')) === false) {
                    throw new \Exception('Could not open resource');
                }

                if (fwrite($this->_resource, $string) === false) {
                    throw new \Exception('Could not write gz encoded string to resource');
                };

                rewind($this->_resource);

                $this->_size = strlen($string);
            }

            public function close()
            {
                fclose($this->_resource);
            }

            public function detach()
            {
                fclose($this->_resource);
            }

            public function getSize()
            {
                return $this->_size;
            }

            public function tell()
            {
                return ftell($this->_resource);
            }

            public function eof()
            {
                return feof($this->_resource);
            }

            public function isSeekable()
            {
                return true;
            }

            public function seek($offset, $whence = SEEK_SET)
            {
                fseek($this->_resource, $offset, $whence);
            }

            public function rewind()
            {
                rewind($this->_resource);
            }

            public function isWritable()
            {
                return false;
            }

            public function write($string)
            {
                throw new \Exception('Cannot write to readonly stream.');
            }

            public function isReadable()
            {
                return true;
            }

            public function read($length)
            {
                return fread($this->_resource, $length);
            }

            public function getContents()
            {
                return stream_get_contents($this->_resource);
            }

            public function getMetadata($key = null)
            {
                $meta = stream_get_meta_data($this->_resource);
                return $key ? $meta[$key] : $meta;
            }

            public function __toString()
            {
                $this->rewind();
                return stream_get_contents($this->_resource);
            }
        };
    }
}
