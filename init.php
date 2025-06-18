<?php
namespace globasa_api;
use Throwable;

try {
    ini_set('log_errors', 1);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    
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
    define("SMALL_IO_DELAY", 5000); // 5k microseconds = a twohundredths of a second
    define("FULL_FILE_DELAY", 50000); // 50k microseconds = a twentieth of a second
    define("GLB_CODE", "art-x-globasa");
    define("GLB_ATTR", "lang=\"art-x-globasa\"");

    // Map spreadsheet column to internal fields
    define('COLUMN_MAP', array(
        'Category' => 'category',
        'Word' => 'term',
        'slug_mod' => 'slug_mod',
        'WordClass' => 'word class',
        'Class' => 'word class',
        'OfficialWord' => 'status',
        'TranslationEng' => 'trans eng',
        'TranslationEpo' => 'trans epo',
        'TranslationSpa' => 'trans spa',
        'TranslationFra' => 'trans fra',
        'TranslationRus' => 'trans rus',
        'TranslationZho' => 'trans zho',
        'TranslationDeu' => 'trans deu',
        'TransNote' => 'entry note',
        'SearchTermsEng' => 'search terms eng',
        'StatusEng' => 'status eng',
        'Synonyms' => 'synonyms',
        'Antonyms' => 'antonyms',
        'Example' => 'example',
        'Tags' => 'tags',
        'LexiliAsel' => 'etymology',
        'See Also' => 'similar natlang',
        'Similar Natlang' => 'similar natlang',
        'TransXRef' => 'entry note', // depracated
        'LexiliEstatus' => 'etymology status', // depracated
    ));
    
    require_once("{$app_path}/models/App_log.php");
    require_once("{$app_path}/vendor/parsedown/Parsedown.php");
    require_once("{$app_path}/vendor/PHPMailer/src/Exception.php");
    require_once("{$app_path}/vendor/PHPMailer/src/PHPMailer.php");
    require_once("{$app_path}/vendor/PHPMailer/src/SMTP.php");
    require_once("{$app_path}/helpers/fetch_files.php");
    require_once("{$app_path}/helpers/load_csv.php");
    require_once("{$app_path}/helpers/partial_debugger.php");
    require_once("{$app_path}/helpers/slugify.php");
    require_once("{$app_path}/models/Dictionary_log.php");
    require_once("{$app_path}/models/Dictionary_comparison.php");
    require_once("{$app_path}/models/Term_parser.php");
    require_once("{$app_path}/controllers/I18n.php");
    require_once("{$app_path}/controllers/Entry_update_controller.php");
    require_once("{$app_path}/controllers/File_controller.php");
}
catch (Throwable $t) {
    pard_print_throwable($t);
    die();
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
        $cfg['log']->add("Development Environment");
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        pard_status(true);
    }
    else {
        pard_status(false);
    }
}
catch (Throwable $t) {
    pard_print_throwable($t);
    die();
}