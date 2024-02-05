<?php

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


function pard(mixed $msg, string $label="", bool $error = false):void {
    global $_pard_status;
    if (!$_pard_status) return;
    if ($error) echo(RED);

    switch (gettype($msg)) {
        case "string":
            if (strlen($msg) < PARD_LENGTH) {
                echo "┣━ ".$label.$msg.PHP_EOL;
            } else {
                $first = true;
                foreach(explode("\n",wordwrap($msg)) as $line) {
                    if ($first) {
                        echo "┣━ ".$label.$line.PHP_EOL;
                        $first = false;
                    } else {
                        echo "┃  ".$line.PHP_EOL;
                    }
                }
            }
            break;
        case "integer":
        case "double":
        case "boolean":
            echo "┣━ ".$label.strval($msg).'(bool)'.PHP_EOL;
            break;
        case 'array':
            echo("┣━┯".$label."(array)".PHP_EOL);
            pard_print_array($msg);
            break;
        case 'object':
            echo("┣━┯".$label.DIM."(object ".get_debug_type($msg).")".TEXT_RESET.PHP_EOL);
            pard_print_object($msg);
            break;
        default:
            echo "┣━ ".$label.DIM."type: ".gettype($msg).PHP_EOL;
            break;
    }
    echo(TEXT_RESET);
}

function pard_app_start():void {
    global $_pard_status;
    if (!$_pard_status) return;
    echo MAGENTA."\n\n
        ┏┓      ┏┓      
        ┣┫┏┓┏┓  ┗┓╋┏┓┏┓╋
        ┛┗┣┛┣┛  ┗┛┗┗┻┛ ┗
          ┛ ┛           
\n".TEXT_RESET;
}

function pard_app_finished():void {
    global $_pard_status;
    if (!$_pard_status) return;
    echo MAGENTA."\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".TEXT_RESET;
}


function pard_status(bool $status):void {
    global $_pard_status;
    $_pard_status = $status;
}

function pard_counter_end():void {
    global $_pard_status;
    if (!$_pard_status) return;

    echo("\r".C0.'3'.CUR_FOR.'[DONE]'.PHP_EOL.C0.CUR_SHOW);
}

function pard_counter_next():void {
    global $_pard_status, $_pard_counter;
    if (!$_pard_status) return;
    $_pard_counter += 1;
    if (!($_pard_counter % 10 === 0)) return;
    echo("\r".C0.'3'.CUR_FOR.sprintf("[%4d]", $_pard_counter));
}

function pard_counter_start(string $msg=""):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    global $_pard_counter;
    $_pard_counter = 0;
    echo "┣━ [0000] ".$msg.C0.CUR_HIDE;
}


/**
 * End pard section
 */
function pard_end(): void {
    global $_pard_section, $_pard_status;
    if (!$_pard_status) return;
    
    if($_pard_section===null) {
        $_pard_section = "";
    }
    echo ("┸ ".DIM.$_pard_section.TEXT_RESET.PHP_EOL);
}

function pard_pause(string $msg = null):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo("┣━ ".$msg.PHP_EOL." (pause) [");
    fgetc(STDIN);
    pard("] GO!".PHP_EOL);
}

function pard_print_array(array $arr, int $i=1):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    foreach($arr as $key=>$data) {
        if (is_array($data)) {
            echo("┃ ".str_repeat("┊ ", $i).$key.":".PHP_EOL);
            pard_print_array($data, $i+1);
        } else {
            echo("┃ ".str_repeat("┊ ", $i).$key.": ".$data.PHP_EOL);
        }
    }
}

function pard_print_object(object $obj, int $i=0) {
    print_r($obj);
}

function pard_print_throwable(Throwable $t) {
    $path_skip = strlen($_SERVER['PWD']) + 1;

    echo("\n".EHLON." ERROR ".$t->getCode()." (Throwable) ".HLOFF.RED."\n");
    pard(msg:"Line ".$t->getLine().": ".substr($t->getFile(), $path_skip), error:true);
    pard(msg:$t->getMessage(), error:true);
    pard(msg:$t->getTrace(), error:true);
}

function pard_progress_end(string $msg=""):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo " done ".$msg.PHP_EOL;
}

function pard_progress_increment(): void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    global $_pard_progress_total, $_pard_progress_count, $_pard_progress_percent;
    $_pard_progress_count += 1;

    $status = floor($_pard_progress_count/$_pard_progress_total*50);

    if ($status>$_pard_progress_percent) {
        $_pard_progress_percent = $status;
        echo("▓");
    }

}

function pard_progress_start(int $total, string $msg=""):void {
    global $_pard_progress_total, $_pard_progress_count, $_pard_progress_percent, $_pard_status;
    if (!$_pard_status) return;
    
    $_pard_progress_total = $total;
    $_pard_progress_count = 0;
    $_pard_progress_percent = 0;
    echo("┣━ ".$msg.": ".$total.PHP_EOL);
    echo("┃  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░\r".C0.'3'.CUR_FOR).C0.CUR_HIDE;
}

function pard_sec(string $name=""):void {
    global $_pard_section, $_pard_status;
    if (!$_pard_status) return;
    
    $_pard_section = $name;
    echo(PHP_EOL.HLON." ".$name." ".TEXT_RESET.PHP_EOL);
}


function pard_step(string $name="."):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo("╏".$name);
}

function pard_step_end():void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo("☑".PHP_EOL.C0.CUR_SHOW);
}
function pard_step_start(string $msg):void {
    global $_pard_status;
    if (!$_pard_status) return;
    
    echo("┣━ steps: ".$msg.PHP_EOL);
    echo("┃  ");
}