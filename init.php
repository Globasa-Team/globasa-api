<?php
namespace globasa_api;
use Throwable;

try {
    ini_set('log_errors', 1);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    require_once("{$app_path}/models/App_log.php");
    $log = new App_log();
    
    define("CONFIG_FILENAME", "config.yaml");
    define("OFFICIAL_WORDS_CSV_FILENAME", "words-official.csv");
    define("OFFICIAL_WORDS_TSV_FILENAME", "words-official.tsv");
    define("OFFICIAL_WORDS_CSV_BACKUP_FILENAME", "-words-official.csv");
    define("OFFICIAL_WORDS_TSV_BACKUP_FILENAME", "-words-official.tsv");
    define("UNOFFICIAL_WORDS_CSV_FILENAME", "words-unofficial.csv");
    define("UNOFFICIAL_WORDS_TSV_FILENAME", "words-unofficial.tsv");
    define("UNOFFICIAL_WORDS_CSV_BACKUP_FILENAME", "-words-unofficial.csv");
    define("UNOFFICIAL_WORDS_TSV_BACKUP_FILENAME", "-words-unofficial.tsv");
    define("I18N_CSV_FILENAME", "i18n.csv");
    define("I18N_YAML_FILENAME", "i18n.yaml");
    define("I18N_CSV_BACKUP_FILENAME", "-i18n.csv");
    define("DATA_FILENAME", "data.yaml");
    
    require_once("{$app_path}/vendor/parsedown-1.7.4/Parsedown.php");
    require_once("{$app_path}/vendor/phpmailer-6.8.0/src/Exception.php");
    require_once("{$app_path}/vendor/phpmailer-6.8.0/src/PHPMailer.php");
    require_once("{$app_path}/vendor/phpmailer-6.8.0/src/SMTP.php");
    require_once("{$app_path}/helpers/fetch_files.php");
    require_once("{$app_path}/helpers/load_csv.php");
    require_once("{$app_path}/models/Dictionary_log.php");
    require_once("{$app_path}/models/Dictionary_comparison.php");
    require_once("{$app_path}/models/Term_parser.php");
    require_once("{$app_path}/controllers/I18n.php");
    require_once("{$app_path}/controllers/Word_list.php");
}
catch (Throwable $e) {
    echo("\nLOAD ERROR\n".
        " Prob\t{$e->getCode()} : {$e->getMessage()}\n".
        " Line\t{$e->getLine()} : {$e->getFile()}\n");
    die();
}

try {
    $data = yaml_parse_file($app_path . DIRECTORY_SEPARATOR . DATA_FILENAME);
    $c = yaml_parse_file($app_path . DIRECTORY_SEPARATOR . CONFIG_FILENAME);
    $c['log'] = $log;
    $c['log']->setEmails($c["app_log_emails"]);
    $c['app_path'] = $app_path;
    $c['parsedown'] = new \Parsedown();
    
    if ($c['dev']) {
        $c['log']->setDebug();
        $c['log']->add("Development Environment");
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    }
    else {
    }
}
catch (Throwable $e) {
    echo("\nCONFIG ERROR
    Prob\t".$e->getCode()." :".$e->getMessage().PHP_EOL.
        "Line\t".$e->getLine()." :".$e->getFile().PHP_EOL);
    die();
}