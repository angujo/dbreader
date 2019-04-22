<?php


namespace Angujo\DBReader\Drivers;


class Helper
{
    public static function array_flatten(array $array, $preserve_keys = 0, &$newArray = [])
    {
        foreach ($array as $key => $child) {
            if (is_array($child)) {
                $newArray = self::array_flatten($child, $preserve_keys, $newArray);
            } elseif ($preserve_keys + is_string($key) > 1) {
                $newArray[$key] = $child;
            } else {
                $newArray[] = $child;
            }
        }
        return $newArray;
    }
}