<?php
// declare(strict_types=1);
namespace WorldlangDict\API;

use Throwable;

ini_set('log_errors', 1);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

define("OPT_SHORT", 'acdlpvw');
define("OPT_LONG", array('file:'));
define("CONFIG_FILENAME", "config/config-entry.yaml");
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
define("DATA_FILENAME", "config/data-entry.yaml");
define("SMALL_IO_DELAY", 5000); // 5k microseconds = a twohundredths of a second
define("FULL_FILE_DELAY", 50000); // 50k microseconds = a twentieth of a second

try {
    require_once(__DIR__ . "/models/App_log.php");
    require_once(__DIR__ . "/vendor/parsedown/Parsedown.php");
    require_once(__DIR__ . "/vendor/PHPMailer/src/Exception.php");
    require_once(__DIR__ . "/vendor/PHPMailer/src/PHPMailer.php");
    require_once(__DIR__ . "/vendor/PHPMailer/src/SMTP.php");
    require_once(__DIR__ . "/src/fetch_files.php");
    require_once(__DIR__ . "/src/load_csv.php");
    require_once(__DIR__ . "/src/partial_debugger.php");
    require_once(__DIR__ . "/src/slugify.php");
    require_once(__DIR__ . "/models/Dictionary_log.php");
    require_once(__DIR__ . "/models/Dictionary_comparison.php");
    require_once(__DIR__ . "/models/Term_parser.php");
    require_once(__DIR__ . "/controllers/I18n.php");
    require_once(__DIR__ . "/controllers/Entry_update_controller.php");
    require_once(__DIR__ . "/controllers/File_controller.php");
} catch (Throwable $t) {
    error_log($t->getMessage());
    print($t->getMessage());
    exit(1);
}

try {
    $cfg = yaml_parse_file($app_path . DIRECTORY_SEPARATOR . CONFIG_FILENAME);
    $data = yaml_parse_file($app_path . DIRECTORY_SEPARATOR . DATA_FILENAME);
    $log = new App_log($cfg);
    $cfg['log'] = $log;
    $cfg['log']->setEmails($cfg["app_log_emails"]);
    $cfg['app_path'] = $app_path;
    $cfg['parsedown'] = new \Parsedown();

    if ($cfg['dev']) {
        $cfg['log']->setDebug();
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    }
} catch (Throwable $t) {
    error_log($t->getMessage());
    print($t->getMessage());
    exit(1);
}
