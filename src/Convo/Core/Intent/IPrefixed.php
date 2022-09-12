<?php declare(strict_types=1);

namespace Convo\Core\Intent;

/**
 * No need for this interface. Providers are used by key already.
 * @author Tole
 * @deprecated
 */
interface IPrefixed
{
	public function accepts($prefix);
}
