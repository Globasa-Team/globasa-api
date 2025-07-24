<?php

namespace globasa_api;

use Throwable;

global
    $cfg,

    $dict,
    $min_entries,
    $basic_entries,
    $standard_entries,

    $term_indexes,
    $search_terms,

    $rhyme_data,
    $examples,
    $derived_data,
    $tags,

    $natlang_count,
    $stats,
    $natlang_etymologies,
    $word_count,
    $category_count,

    $import_report,
    $dev_report,

    $old_csv_filename,
    $new_csv_data,
    $old_csv_data,

    $debug_data,
    $debug_mode;

// Initialize global data
$cfg = [];

$dict = [];
$min_entries = [];
$basic_entries = [];
$standard_entries = [];

$term_indexes = [];
$search_terms = [];

$rhyme_data = [];
$tags = [];
$derived_data = [];

$natlang_count = [];
$stats = [];
$natlang_etymologies = [];
$word_count = 0;
$category_count = [];

$import_report = [];
$dev_report = [];

$old_csv_data = [];
$new_csv_data = [];

$debug_data = [];
$debug_mode = false;

// Initialize local data
$new_csv_filename = "";

$app_path = __DIR__;
require_once("{$app_path}/init.php");
try {
    // Startup
    \pard\app_start();
    \pard\sec("Initiation");
    if (count($argv) > 1) {
        \pard\m('Parsing parameters');
        
        if ($argv[1] === 'd') {
            \pard\m("Debug Output Mode (only writing some entries)");
            $debug_mode = true;
        } else {
            $debug_mode = false;
        }
    
        if ($argv[1] === 'r' || $argv[1] === 'd') {
            \pard\m("Reprocessing previous CSV file");
            $new_csv_filename = $data['previous'];
        } else {
            $new_csv_filename = $argv[1];
            \pard\m("Using: ".$new_csv_filename);
        }
    } else {
        $debug_mode = false;
        \pard\m('No arguments. Exiting.');
        \pard\end();
        \pard\app_finished();
        echo("\nUsage: php [-d display_errors=on] word-list-import.php [r][d][filename]\n\n");
        exit(0);
    }


    // \pard\m("Loading examples.yaml");
    // $examples = yaml_parse_file('temp/examples.yaml');
    $cfg['log']->add("Environment: " . ($cfg['dev'] ? 'dev' : 'production'), 1);
    \pard\m($debug_mode, "Debug mode");
    \pard\end();
    
    // Update data files
    Entry_update_controller::update_entries(old_csv_filename:$data['previous'], current_csv_filename:$new_csv_filename);

    \pard\sec("Other stuff");

    // Update i18n
    $cfg['log']->add("Updating I18n", 5);
    I18n::update();

    // Finish up
    // \pard\m($import_report, "Parse report");
    // \pard\m($dev_report, "Developer report");
    $cfg['log']->add_report($import_report, "Import Report");
    $cfg['log']->add_report($dev_report, "Developer Report");
    $cfg['log']->add("Script complete", 5);
    $cfg['log']->email_log($cfg);
    yaml_emit_file(DATA_FILENAME, ['previous'=>$new_csv_filename]);

    
    \pard\end();
}
catch (Throwable $t) {
    \pard\print_throwable($t);
}
\pard\app_finished();