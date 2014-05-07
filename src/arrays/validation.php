<?php
namespace arrays\validation {

    function matchers()
    {
        return [
            'int' => 'is_int',
            'hello' => 'hello',
            'gt' => function ($value, $n) {
                    return $value > $n;
                },
            'in' => function ($value) {
                    $args = func_get_args();
                    array_shift($args);
                    return in_array($value, $args);
                },
        ];
    }

    function check_matcher($matcherString, $value, $matchers)
    {
        $args = [];
        foreach (explode(' ', $matcherString) as $name) {
            if (substr($name, -1) == ')') {
                list($name, $args) = explode('(', $name);
                $args = explode(',', rtrim($args, ')'));
            }
            if (!isset($matchers[$name])) {
                throw new \InvalidArgumentException("Matcher $name not found");
            } elseif (is_callable($matchers[$name])) {
                if (!call_user_func_array($matchers[$name], array_merge([$value], $args))) {
                    return false;
                }
            } else if ($matchers[$name] !== $value) {
                return false;
            }
        }

        return true;
    }

    function get_expected_counters(array $schema)
    {
        $expectedCounters = [];
        foreach ($schema as $key => $item) {
            $nested = null;
            if (is_array($item)) {
                $nested = $item;
                $item = $key;
            }
            if (substr($item, -1) == '?') {
                $expectedCounters[substr($item, 0, -1)] = [0, 1, $nested];
            } elseif (substr($item, -1) == '!') {
                $expectedCounters[substr($item, 0, -1)] = [1, 1, $nested];
            } elseif (substr($item, -1) == '}') {
                list($item, $quantifier) = explode('{', $item);
                $range = explode(',', rtrim($quantifier, '}'));
                if (count($range) == 1) {
                    $expectedCounters[$item] = [intval($range), intval($range), $nested];
                } else {
                    list($min, $max) = $range;
                    $expectedCounters[$item] = [
                        $min === '' ? 0 : intval($min),
                        $max === '' ? PHP_INT_MAX : intval($max),
                        $nested
                    ];
                }
            } elseif (substr($item, -1) == '*') {
                $expectedCounters[substr($item, 0, -1)] = [0, PHP_INT_MAX, $nested];
            } elseif (substr($item, 0, 1) == ':') {
                $expectedCounters[$item] = [0, PHP_INT_MAX, $nested];
            } else {
                $expectedCounters[$item] = [1, 1, $nested];
            }
        }

        return $expectedCounters;
    }

    function increment_counter(array $expectedCounters, array &$counters, $keyPattern, $value)
    {
        if ($expectedCounters[$keyPattern][2]) {
            if (is_array($value)
                && array_keys_valid($value, $expectedCounters[$keyPattern][2])
            ) {
                $counters[$keyPattern]++;
            }
        } else {
            $counters[$keyPattern]++;
        }
    }
}
