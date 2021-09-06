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

    public function __construct($logger)
    {
        $this->_logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $body = trim($response->getBody()->__toString());

        $this->_logger->info('Original body ['.$body.']');

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

            private $_filename;

            private $_size;

            public function __destruct()
            {
                $this->close();
            }

            public function __construct($logger, $string)
            {
                $this->_logger = $logger;

                if (($this->_filename = tempnam(sys_get_temp_dir(), 'gz')) === false) {
                    throw new \Exception('Could not create temporary file to write gz string to');
                }

                $this->_logger->info("Created tmp file at [$this->_filename]");

                if (($this->_resource = fopen($this->_filename, 'a+')) === false) {
                    throw new \Exception('Could not open resource');
                }

                if (fwrite($this->_resource, $string) === false) {
                    throw new \Exception('Could not write gz encoded string to resource');
                };

                $this->_logger->info('Wrote ['.$string.'] to ['.$this->_filename.']');

                rewind($this->_resource);

                $this->_size = strlen($string);
            }

            public function close()
            {
                fclose($this->_resource);
                unlink($this->_filename);
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
                $str = stream_get_contents($this->_resource);
                return $str;
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
