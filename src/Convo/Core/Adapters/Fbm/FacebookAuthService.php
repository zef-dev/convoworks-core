<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Fbm;

class FacebookAuthService
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    public function __construct($logger)
    {
        $this->_logger = $logger;
    }

    public function verifyPayloadVerity($appsecret, $header, $payload)
    {
        $expected_sig = hash_hmac('sha1', $payload, $appsecret);

        $sig = '';

        if (strlen($header) === 45 && substr($header, 0, 5) === 'sha1=') {
            $sig = substr($header, 5);
        }

        return hash_equals($sig, $expected_sig);
    }
}