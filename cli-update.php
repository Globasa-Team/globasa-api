<?php
namespace globasa_api;
$app_path = dirname(__FILE__).'/';
require_once("{$app_path}/init.php");
$c['app_path'] = $app_path;


$recent_files = update_source_files($c);
// Save current filename for comparison next time
yaml_emit_file(
    $app_path.DATA_FILENAME,
    $recent_files
);

$r = log_changes($c['api_path'].OFFICIAL_WORDS_CSV_FILENAME, $data['backup_official_csv'], $c);

function update_source_files($c) {

    // Get formatted date
    $datetime = date(DATE_ATOM);
    $month = date('m');
    $year = date('Y');

    // Check backup folder exists
    $backup_path = $c['app_path'] .".backup/{$year}/{$month}";
    if(!is_dir($backup_path)) {
        mkdir($backup_path, 0700, true);
    }
    
    $backup_files = [];
    $backup_files['backup_official_csv'] = $backup_path . "/{$datetime}".OFFICIAL_WORDS_CSV_BACKUP_FILENAME;
    $backup_files['backup_official_tsv'] = $backup_path . "/{$datetime}".OFFICIAL_WORDS_TSV_BACKUP_FILENAME;
    $backup_files['backup_unofficial'] = $backup_path . "/{$datetime}".UNOFFICIAL_WORDS_CSV_BACKUP_FILENAME;
    $backup_files['backup_i18n'] = $backup_path . "/{$datetime}".I18N_CSV_BACKUP_FILENAME;

    // Fetch files
    fetch_files([
        'official_words_url' => [
            'url' => $c['official_words_csv_url'],
            'filenames' => [
                $c['api_path'].OFFICIAL_WORDS_CSV_FILENAME,
                $backup_files['backup_official_csv']
            ],
        ],

        'official_words_tsv_url' => [
            'url' => $c['official_words_tsv_url'],
            'filenames' => [
                $c['api_path'].OFFICIAL_WORDS_TSV_FILENAME,
                $backup_files['backup_official_tsv']
            ],
        ],

        // 'unofficial_words_url' => [
        //     'url' => $c['unofficial_words_url'],
        //     'filenames' => [
        //         $c['api_path'].UNOFFICIAL_WORDS_CSV_FILENAME,
        //         $backup_files['backup_unofficial'].OFFICIAL_WORDS_CSV_BACKUP_FILENAME
        //     ],
        // ],

        'i18n_url' => [
            'url' => $c['i18n_url'],
            'filenames' => [
                $c['api_path'].I18N_CSV_FILENAME,
                $backup_files['backup_i18n']
            ],
        ]
    ]);

    return ($backup_files);
}


function log_changes($new_filename, $old_filename, $c) {
    $new = loadCsv($new_filename);
    $old = loadCsv($old_filename);
    
    // Find changes
    $comparison = new DictionaryComparison($old, $new, $c);
    // Log changes
    $log = new DictionaryLog($c);
    $log->add($comparison->changes);
}