<?php

if (phpversion() < 80500) { // PHP 8.5

    function array_first(array $array): mixed
    {
        return $array === [] ? null : $array[array_key_first($array)];
    }

    function array_last(array $array): mixed
    {
        return $array === [] ? null : $array[array_key_last($array)];
    }
}
