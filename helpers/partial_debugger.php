<?php
/**
 * Partial Solution Command Line Interface debugger.
 * 
 * 
 */

declare(strict_types=1);
namespace pard;

define('COL', "\033[");
define('COLEND', "\033[0m");
define('WHITE', "\033[39m");
define('GRAY', "\033[90m");
define('RED', "\033[31m");
define('MAGENTA', "\033[35m");
define('DIM', "\033[2m");
define('BOLD', "\033[1m");
define('HLON', "\033[7m");
define('HLOFF', "\033[40m");
define('EHLON', "\033[41m");
define('TEST', "\033[7m");
define('TEXT_RESET',"\033(B\e[m");

// C0 Control Code
define('C0', "\033[");
define('CUR_UP', 'A');
define('CUR_DOWN', 'B');
define('CUR_FOR', 'C');
define('CUR_BACK', 'D');
define('CUR_HIDE', '25l');
define('CUR_SHOW', '25h');

define('PARD_LENGTH', 50);
// Eg. `C0.'5'.CUR_BACK` to go backward 5 characters.

// https://blog.devgenius.io/writing-beautiful-cli-programs-6fc3e3728c8b
// https://gist.github.com/fnky/458719343aabd01cfb17a3a4f7296797
// \b back one character
// \r start of line
/**
 * \033[nA Moves the cursor n rows/lines up
 * \033[nB Moves the cursor n rows/lines down
 * \033[nC Moves the cursor n cells/columns forward
 * \033[nD Moves the cursor n cells/columns back
 */


/**
 * Display message
 */
function m(mixed $msg, string $label="", bool $error = false):void {
    global $_pard_status;
    if (!$_pard_status) return;
    if ($error) echo(RED);
    if (!empty($label)) $label = GRAY.$label.': '.TEXT_RESET;

    switch (gettype($msg)) {
        case "string":
            if (strlen($msg) < PARD_LENGTH) {
                echo("┠─ ".$label.$msg.PHP_EOL);
            } else {
                $first = true;
                foreach(explode("\n",wordwrap($msg)) as $line) {
                    if ($first) {
                        echo("┠─ ".$label.': '.$line.PHP_EOL);
                        $first = false;
                    } else {
                        echo "┃  ".$line.PHP_EOL;
                    }
                }
            }
            break;
        case "integer":
            echo "┠─ ".$label.number_format($msg).GRAY.'(integer)'.TEXT_RESET.PHP_EOL;
            break;
        case "float":
            echo "┠─ ".$label.number_format($msg, 2).GRAY.'(float)'.TEXT_RESET.PHP_EOL;
            break;
        case "boolean":
            echo "┠─ ".$label.strval($msg).GRAY.'(bool)'.TEXT_RESET.PHP_EOL;
            break;
        case 'array':
            echo("┠─┬".$label.GRAY."(array)".TEXT_RESET.PHP_EOL);
            print_array($msg);
            break;
        case 'Error':
        case 'Exception':
            echo("┠─┬".$label.GRAY.'('.gettype($msg).')'.TEXT_RESET.PHP_EOL);
            print_array($msg);
            m("TEST END");
        case 'object':
            echo("┠─┬".$label.GRAY."(object ".get_debug_type($msg).")".TEXT_RESET.PHP_EOL);
            print_object($msg);
            break;
        default:
            echo "┠─ ".$label.GRAY."other type: ".gettype($msg).TEXT_RESET.PHP_EOL;
            break;
    }
    echo(TEXT_RESET);
}

function app_start(?bool $status = null):void {
    global $_pard_status;
    if ($status !== null) $_pard_status = $status;
    if (!$_pard_status) return;
    echo MAGENTA."
    ┏┓      ┏┓      
    ┣┫┏┓┏┓  ┗┓╋┏┓┏┓╋
    ┛┗┣┛┣┛  ┗┛┗┗┻┛ ┗
      ┛ ┛           
".TEXT_RESET;
}

function app_finished():void {
    global $_pard_status;
    if (!$_pard_status) return;
    echo MAGENTA."\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".TEXT_RESET;
    
    // DEBUG: Memory Usage
    m(memory_get_usage(true), "Memory usage (real)");
    m(memory_get_usage(), "Memory usage");
}


function status(bool $status):void {
    global $_pard_status;
    $_pard_status = $status;
}

function counter_end():void {
    global $_pard_status;
    if (!$_pard_status) return;

    echo("\r".C0.'3'.CUR_FOR.TEXT_RESET.'[DONE]'.TEXT_RESET.PHP_EOL.C0.CUR_SHOW);
}

function counter_next():void {
    global $_pard_status, $_pard_counter;
    if (!$_pard_status) return;
    $_pard_counter += 1;
    if (!($_pard_counter % 10 === 0)) return;
    echo("\r".C0.'3'.CUR_FOR.sprintf("[%4d]", $_pard_counter));
}

function counter_start(string $msg=""):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    global $_pard_counter;
    $_pard_counter = 0;
    echo "┠─ [0000] ".$msg.C0.CUR_HIDE;
}


/**
 * End pard section
 */
function end(): void {
    global $_pard_section, $_pard_status;
    if (!$_pard_status) return;
    
    if($_pard_section===null) {
        $_pard_section = "";
    }
    echo ("┸ ".GRAY.$_pard_section.TEXT_RESET.PHP_EOL);
}

function header(string $msg) {
    echo(PHP_EOL.MAGENTA.$msg.TEXT_RESET.PHP_EOL);
}

function pause(string $msg = ''):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo("┠─ ".$msg.PHP_EOL." (pause) [");
    fgetc(STDIN);
    m("] GO!".PHP_EOL);
}

function print_array(array $arr, int $i=1):void {
    global $_pard_status;
    if (!$_pard_status) return;

    foreach($arr as $key=>$data) {
        if (is_array($data)) {
            echo("┃ ".str_repeat("┊ ", $i).$key.":".PHP_EOL);
            print_array($data, $i+1);
        } else {
            echo("┃ ".str_repeat("┊ ", $i).$key.": ".$data.PHP_EOL);
        }
    }
    if (!count($arr)) {
        echo("┃ ".str_repeat("┊ ", $i)."(empty array)".PHP_EOL);
    }
}

function print_object(object $obj, int $i=0) {
    print_r($obj);
}

function print_throwable(\Throwable $t) {
    $path_skip = strlen($_SERVER['PWD']) + 1;

    echo("\n".EHLON." ERROR ".$t->getCode()." (Throwable) ".HLOFF.RED."\n");
    echo("┃ ┊ Line ".$t->getLine().": ".substr($t->getFile(), $path_skip)."\n");
    echo("┃ ┊ Message: ".$t->getMessage()."\n");
    echo("┃ ┊ Stack trace:\n");
    print_array($t->getTrace(), 2);
}

function progress_end(string $msg=""):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo "\r".C0.'53'.CUR_FOR.GRAY.' '.(empty($msg)?"done":$msg).TEXT_RESET.PHP_EOL;
}

function progress_increment(): void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    global $_pard_progress_total, $_pard_progress_count, $_pard_progress_percent;
    $_pard_progress_count += 1;

    $status = intval(floor(50.0*$_pard_progress_count/$_pard_progress_total));

    if ($status>$_pard_progress_percent) {
        $inc = $status - $_pard_progress_percent;
        $_pard_progress_percent = $status;
        echo(str_repeat("▓", $inc));
    }

}

function progress_start(int $total, string $msg=""):void {
    global $_pard_progress_total, $_pard_progress_count, $_pard_progress_percent, $_pard_status;
    if (!$_pard_status) return;
    
    $_pard_progress_total = $total;
    $_pard_progress_count = 0;
    $_pard_progress_percent = 0;
    echo("┠─ ".$msg.": ".$total.PHP_EOL);
    echo("┃  ".str_repeat('░',50)."\r".C0.'3'.CUR_FOR).C0.CUR_HIDE;
}

function sec(string $name=""):void {
    global $_pard_section, $_pard_status;
    if (!$_pard_status) return;
    
    $_pard_section = $name;
    echo(PHP_EOL.HLON." ".$name." ".TEXT_RESET.' '.PHP_EOL);
}


function step(string $name="."):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo("╏".$name);
}

function step_end():void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo("☑".PHP_EOL.C0.CUR_SHOW);
}
function step_start(string $msg):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo("┠─ steps: ".$msg.PHP_EOL);
    echo("┃  ");
}