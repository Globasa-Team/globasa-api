<?php
define('COL', "\033[");
define('COLEND', "\033[0m");
define('WHITE', "\033[39m");
define('GRAY', "\033[90m");
define('RED', "\033[31m");
define('DIM', "\033[2m");
define('BOLD', "\033[1m");
define('HLON', "\033[7m");
define('HLOFF', "\033[27m");
define('TEST', "\033[7m");
define('TEXT_RESET',"\033(B\e[m");

function pard_sec(string $name) {
    echo(PHP_EOL.HLON." ".$name." ".TEXT_RESET.PHP_EOL);
}

function pard(mixed $msg) {
    switch (gettype($msg)) {
        case "integer":
        case "double":
        case "string":
            echo "┣━ ".$msg.PHP_EOL;
            break;
        case 'array':

            echo("┣━ ".DIM."(array)".TEXT_RESET.PHP_EOL);
            pard_print_array($msg, 2);
            break;
        default:
            echo "┣━ ".DIM."type: ".gettype($msg).PHP_EOL;
            break;
    }
    echo(TEXT_RESET);
}

function pard_print_array($arr, int $i) {
    foreach($arr as $key=>$data) {
        if(is_string($data)) {
            echo("┃  ".str_repeat(" ", $i).$key.": ".$data.PHP_EOL);
        }
        if (is_array($data)) {
            echo("┃  ".str_repeat(" ", $i).$key.":".PHP_EOL);
            pard_print_array($data, $i+2);
        }
    }
}

// ├ ┣  ― ⍽ ⎸ ⎹ ␣ ─ ━ │ ┃
// 🭶 	🭷 	🭸 	🭹 	🭺 	🭻