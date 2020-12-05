<?php declare(strict_types=1);

namespace Convo\Core\Intent;

interface IPrefixed
{
	public function accepts($prefix);
}
