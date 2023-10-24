<?php
namespace globasa_api;

use Throwable;

try {
    $app_path = __DIR__;
    require_once("{$app_path}/init.php");
    $c['log']->add("Using: " . $argv[1]);
    $c['log']->add("Environment: " . ($c['dev'] ? 'dev' : 'production'));
    $csv_data = Update_controller::load_current_terms($c, $argv[1]);
    Update_controller::log_changes($csv_data, $data['previous'], $c);
    yaml_emit_file(DATA_FILENAME, ['previous'=>$argv[1]]);
    Update_controller::update_i18n($c);
    $c['log']->add("Script complete");
    $c['log']->email_log($c);
    
}
catch (Throwable $e) {
    echo("\nCAUGHT
    Prob\t".$e->getCode()." :".$e->getMessage().PHP_EOL.
        "Line\t".$e->getLine()." :".$e->getFile().PHP_EOL);
}