<?php declare(strict_types=1);

namespace Convo\Core;

class SessionEndedException extends StateChangedException
{
    public function __construct($previous = null)
    {
        parent::__construct('session_ended', $previous);
    }
}