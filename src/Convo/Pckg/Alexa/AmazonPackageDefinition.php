<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa;

use Convo\Core\Factory\AbstractPackageDefinition;

class AmazonPackageDefinition extends AbstractPackageDefinition
{
	const NAMESPACE	=	'convo-alexa';

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

	public function __construct(
	    \Psr\Log\LoggerInterface $logger,
        \Convo\Core\Util\IHttpFactory $httpFactory,
        \Convo\Core\IServiceDataProvider $convoServiceDataProvider
    ) {
        $this->_httpFactory = $httpFactory;
        $this->_convoServiceDataProvider = $convoServiceDataProvider;

		parent::__construct( $logger, self::NAMESPACE, __DIR__);
	}

}
