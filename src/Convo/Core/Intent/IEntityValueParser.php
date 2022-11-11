<?php
declare(strict_types = 1);

namespace Convo\Core\Intent;

interface IEntityValueParser
{

    /**
     * @param mixed $raw
     * @return string
     */
    public function parseValue( $raw);
    
}