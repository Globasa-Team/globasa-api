<?php
namespace globasa_api;

require_once("init.php");

$jan1 = "2023/00/word-list-official-2023-00-00.csv";
$march4 = "2023/03/2023-03-04T23:31:58-05:00-words-official.csv";
$march22 = "2023/03/2023-03-22T14:56:21-04:00-words-official.csv";
$march23 = "2023/03/2023-03-23T09:35:54-04:00-words-official.csv";
$march27 = "2023/03/2023-03-27T15:23:05-04:00-words-official.csv";
$march28 = "2023/03/2023-03-28T08:36:50-04:00-words-official.csv";

$recent_files = update_source_files($c);
$r = log_changes($c['api_path'].OFFICIAL_WORDS_CSV_FILENAME, $data['backup_official'], $c);
// $r = log_changes($c['backup_path'].$march28, $c['backup_path'].$march27, $c);
// $r = log_changes($c['backup_path'].$march28, $c['backup_path'].$jan1, $c);


// Save current filename for comparison next time
// yaml_emit_file(
//     DATA_FILENAME,
//     $recent_files
// );


function update_source_files($c) {

    // Get formatted date
    $datetime = date(DATE_ATOM);
    $month = date('m');
    $year = date('Y');

    // Check backup folder exists
    $backup_path = $c['backup_path']."{$year}/{$month}";
    if(!is_dir($backup_path)) {
        mkdir($backup_path, 0700, true);
    }
    
    $backup_files = [];
    $backup_files['backup_official'] = $backup_path . "/{$datetime}".OFFICIAL_WORDS_CSV_BACKUP_FILENAME;
    $backup_files['backup_unofficial'] = $backup_path . "/{$datetime}".UNOFFICIAL_WORDS_CSV_BACKUP_FILENAME;
    $backup_files['backup_i18n'] = $backup_path . "/{$datetime}".I18N_CSV_BACKUP_FILENAME;

    // Fetch files
    fetch_files([
        'official_words_url' => [
            'url' => $c['official_words_url'],
            'filenames' => [
                $c['api_path'].OFFICIAL_WORDS_CSV_FILENAME,
                $backup_files['backup_official']
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
            'url' => $c['official_words_url'],
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