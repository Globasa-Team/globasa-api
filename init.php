<?php
namespace globasa_api;

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
require_once("{$app_path}/models/App_log_model.php");
require_once("{$app_path}/controllers/Update_controller.php");


$data = yaml_parse_file($app_path . DIRECTORY_SEPARATOR . DATA_FILENAME);
$c = yaml_parse_file($app_path . DIRECTORY_SEPARATOR . CONFIG_FILENAME);
$c['app_path'] = $app_path;

if ($c['dev']) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    $c['log'] = new App_log($c["app_log_emails"], true);
    $c['log']->add("Development Environment");

}
else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    $c['log'] = new App_log(["app_log_emails"], false);
}