<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa;

use Convo\Core\Factory\AbstractPackageDefinition;

class AmazonPackageDefinition extends AbstractPackageDefinition
{
	const NAMESPACE	=	'convo-alexa';

	public function __construct( \Psr\Log\LoggerInterface $logger)
	{
		parent::__construct( $logger, self::NAMESPACE, __DIR__);
	}

}
