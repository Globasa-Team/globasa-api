<?php
declare(strict_types=1);
namespace WorldlangDict\API;

use Throwable;

mb_internal_encoding('UTF-8'); 

ini_set('log_errors', 1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define("I18N_CSV_FILENAME", "i18n.csv");
define("I18N_YAML_FILENAME", "i18n.yaml");

require_once("controllers/I18n.php");

$config = yaml_parse_file("config/config-i18n.yaml");
I18n::update($config['api_path']);
