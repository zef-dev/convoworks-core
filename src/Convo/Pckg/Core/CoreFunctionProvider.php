<?php declare(strict_types=1);

namespace Convo\Pckg\Core;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class CoreFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function __construct() {}

    public function getFunctions()
    {
        $functions = [];

        $functions[] = ExpressionFunction::fromPhp('count');
        $functions[] = ExpressionFunction::fromPhp('rand');
        $functions[] = ExpressionFunction::fromPhp('strtolower');
        $functions[] = ExpressionFunction::fromPhp('strtoupper');
        $functions[] = ExpressionFunction::fromPhp('date');
        $functions[] = ExpressionFunction::fromPhp('is_numeric');
        $functions[] = ExpressionFunction::fromPhp('is_int');
        $functions[] = ExpressionFunction::fromPhp('intval');
        $functions[] = ExpressionFunction::fromPhp('ceil');
        $functions[] = ExpressionFunction::fromPhp('floor');
        $functions[] = ExpressionFunction::fromPhp('strlen');
        $functions[] = ExpressionFunction::fromPhp('array_rand');
        $functions[] = ExpressionFunction::fromPhp('array_values');
        $functions[] = ExpressionFunction::fromPhp('urlencode');
        $functions[] = ExpressionFunction::fromPhp('str_replace');
        $functions[] = ExpressionFunction::fromPhp('array_splice');
        $functions[] = ExpressionFunction::fromPhp('is_array');
        $functions[] = ExpressionFunction::fromPhp('in_array');
        $functions[] = ExpressionFunction::fromPhp('array_search');
        $functions[] = ExpressionFunction::fromPhp('array_merge');
        $functions[] = ExpressionFunction::fromPhp('is_numeric');
        $functions[] = ExpressionFunction::fromPhp('substr');
        $functions[] = ExpressionFunction::fromPhp('stripos');
        $functions[] = ExpressionFunction::fromPhp('strtotime');
        $functions[] = ExpressionFunction::fromPhp('time');

        $unwrap_cw_resolvers = function ($args, $data) use (&$unwrap_cw_resolvers) {
            if (is_array($data)) {
                $clean = [];

                foreach ($data as $key => $val) {
                    $clean[$key] = $unwrap_cw_resolvers($args, $val);
                }

                return $clean;
            }

            if (is_a($data, '\Zef\Zel\IValueAdapter')) {
                /** @var \Zef\Zel\IValueAdapter $data */
                return $data->get();
            }

            return $data;
        };

        $functions[] = new ExpressionFunction(
            'unwrap_cw_resolvers',
            function ($data) {
                return sprintf('unwrap_cw_resolvers(%1$data)', $data);
            },
            $unwrap_cw_resolvers
        );

        $functions[] = new ExpressionFunction(
            'array_shuffle',
            function ($array) {
                return sprintf('(is_array(%1$a) ? array_shuffle(%1$a) : %1$a', $array);
            },
            function($args, $array) {
                if (!is_array($array)) {
                    return $array;
                }

                $copy = $array;
                shuffle($copy);
                return $copy;
            }
        );

        $functions[] = new ExpressionFunction(
            'array_push',
            function ($array, $item) {
                return sprintf('(is_array(%1$a) ? array_push(%1$a, %2$i) : %1$a', $array, $item);
            },
            function($args, $array, $item) {
                if (!is_array($array)) {
                    return $array;
                }

                $copy = $array;
                $copy[] = $item;
                return $copy;
            }
        );

        $functions[] = new ExpressionFunction(
            'decode_special_chars',
            function ($string) {
                return sprintf('(is_string(%1$a) ? decode_special_chars(%1$a) : %1$a', $string);
            },
            function($args, $string) {
                if (!is_string($string)) {
                    return $string;
                }

                $string = html_entity_decode( $string, ENT_QUOTES);
                $string = htmlspecialchars_decode( $string);
                $string = urldecode( $string);
                return $string;
            }
        );

        $functions[] = new ExpressionFunction(
            'array_sort_by_field',
            function ($array, $field) {
                return sprintf('(is_array(%1$a) ? array_sort_by_field(%1$a, %2$a) : %1$a', $array);
            },
            function($args, $array, $field) {
                if (!is_array($array)) {
                    return $array;
                }
//                 $this->_logger->debug( 'helou'.print_r( $args, true));
                usort($array, function ($a, $b) use ($field) {return strcmp( $a[$field], $b[$field]);});
                return $array;
            }
        );

        $functions[] = new ExpressionFunction(
            'ordinal',
            function ($string) {
                return sprintf('(is_numeric(%1$a) ? ordinal(%1$a) : %1$a', $string);
            },
            function($args, $string) {
                if (!is_numeric($string)) {
                    return $string;
                }

                $integer = intval( $string);
                $ends = array('th','st','nd','rd','th','th','th','th','th','th');

                if( (($integer % 100) >= 11) && (($integer%100) <= 13)) {
                    return $integer. 'th';
                } else {
                    return $integer. $ends[$integer % 10];
                }
            }
        );

        $functions[] = new ExpressionFunction(
            'human_concat',
            function ($array,  $conjunction) {
                return sprintf('(is_array(%1$a) ? human_concat(%1$a, %2$a) : %1$a', $array);
            },

            function($args, $array, $conjunction = null) {
                if (!is_array($array)) {
                    return $array;
                }

                $last  = array_slice( $array, -1);
                $first = join(', ', array_slice($array, 0, -1));
                $both  = array_filter( array_merge( array( $first), $last), 'strlen');

                if( $conjunction) {
                    return join(' '.$conjunction.' ', $both);
                }

                return join(', ', $both);
            }
        );

        return $functions;
    }
}