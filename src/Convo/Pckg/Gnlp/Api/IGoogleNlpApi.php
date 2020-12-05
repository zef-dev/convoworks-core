<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Api;

interface IGoogleNlpApi
{

    public function analyzeTextSyntax( $text);

    public function analyzeTextSentiment( $text);

    public function analyzeTextEntities( $text);

}