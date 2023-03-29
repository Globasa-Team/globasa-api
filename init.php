<?php
namespace globasa_api;

define("CONFIG_FILENAME", "config.yaml");
define("OFFICIAL_WORDS_CSV_FILENAME", "words-official.csv");
define("OFFICIAL_WORDS_CSV_BACKUP_FILENAME", "-words-official.csv");
define("UNOFFICIAL_WORDS_CSV_FILENAME", "words-unofficial.csv");
define("UNOFFICIAL_WORDS_CSV_BACKUP_FILENAME", "-words-unofficial.csv");
define("I18N_CSV_FILENAME", "i18n.csv");
define("I18N_CSV_BACKUP_FILENAME", "-i18n.csv");
define("DATA_FILENAME", "data.yaml");

$c = yaml_parse_file(CONFIG_FILENAME);
$data = yaml_parse_file(DATA_FILENAME);

if (strcmp($c['env'],"dev")==0) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    echo("\nDevelopment Environment\n");
}

require_once("vendor/parsedown-1.7.4/Parsedown.php");
require_once("helpers/fetch_files.php");
require_once("helpers/load_csv.php");
require_once("models/DictionaryLogModel.php");
require_once("models/DictionaryComparisonModel.php");
