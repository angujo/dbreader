<?php


/**
 * @param array $array
 * @param mixed ...$args
 *
 * @return array
 */
function array_add(array &$array, ...$args)
{
    foreach ($args as $key => $arg) {
        if (is_array($arg)) {
            $i = 0;
            foreach ($arg as $k => $value) {
                if ($i === $k) {
                    $i++;
                    $array[] = $value;
                } else {
                    $array[$k] = $value;
                }
            }
        } else {
            $array[] = $arg;
        }
    }
    return $array;
}