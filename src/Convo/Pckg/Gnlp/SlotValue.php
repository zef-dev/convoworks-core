<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp;

class SlotValue
{
    private $_pairs = [];

    public function __construct()
    {
    }

    public function addTokenValue($value, GoogleNlSyntaxToken $token)
    {
        $this->_pairs[] = [
            'value' => $value,
            'token' => $token
        ];
    }

    public function getTokenValue( GoogleNlSyntaxToken $token)
    {
        foreach ($this->_pairs as $pair) {
            /** @var GoogleNlSyntaxToken $pair_token */
            $pair_token = $pair['token'];
            $pair_value = $pair['value'];

            if ($token->equals($pair_token)) {
                return $pair_value;
            }
        }

        throw new \Convo\Core\DataItemNotFoundException("No value for token [$token]");
    }

    // UTIL
    public function __toString()
    {
        return get_class($this).'['.count($this->_pairs).']';
    }
}