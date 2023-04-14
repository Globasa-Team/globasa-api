<?php
namespace globasa_api;
$app_path = dirname(__FILE__).'/';
require_once("{$app_path}/init.php");


// Download from update sources & save filename for comparison next time
if(!$c['dev'] || $c['dev_update_sources']) {
    $recent_files = update_source_files($c);
    yaml_emit_file(
        $app_path.DATA_FILENAME,
        $recent_files
    );
}
else $c['log']->add("Skipped source update");

if(!$c['dev'] || $c['dev_process_changes']) {
    $r = log_changes($c['api_path'].OFFICIAL_WORDS_CSV_FILENAME, $data['backup_official_csv'], $c);
}
else $c['log']->add("Skipped logging changes");

if(!$c['dev'] || $c['dev_email_log']) {
    $c['log']->email_log($c);
}
else $c['log']->add("Skipped emailing log");


function update_source_files($c) {
    // Check backup folder exists
    $backup_path = $c['app_path'] . ".backup/" . date('Y/m');
    $backup_prepend = $backup_path . DIRECTORY_SEPARATOR . date(DATE_ATOM);
    if(!is_dir($backup_path)) {
        mkdir($backup_path, 0700, true);
    }
    
    $backup_files = [];
    $backup_files['backup_official_csv'] = $backup_prepend.OFFICIAL_WORDS_CSV_BACKUP_FILENAME;
    $backup_files['backup_official_tsv'] = $backup_prepend.OFFICIAL_WORDS_TSV_BACKUP_FILENAME;
    $backup_files['backup_unofficial'] = $backup_prepend.UNOFFICIAL_WORDS_CSV_BACKUP_FILENAME;
    $backup_files['backup_i18n'] = $backup_prepend.I18N_CSV_BACKUP_FILENAME;

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
    $c['log']->add("Changes logged: ".count($comparison->changes));
}