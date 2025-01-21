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



try {
    // Startup
    $app_path = __DIR__;
    require_once("{$app_path}/init.php");
    pard_app_start();
    pard_sec("Initiation");

    if ($argv[1] === 'd') {
        pard("Debug Output Mode (only writing some entries)");
        $debug_mode = true;
    } else {
        $debug_mode = false;
    }

    if ($argv[1] === 'r' || $argv[1] === 'd') {
        pard("Reprocessing previous CSV file");
        $new_csv_filename = $data['previous'];
    } else {
        $new_csv_filename = $argv[1];
        pard("Using: ".$new_csv_filename);
    }

    // pard("Loading examples.yaml");
    // $examples = yaml_parse_file('temp/examples.yaml');
    $cfg['log']->add("Environment: " . ($cfg['dev'] ? 'dev' : 'production'), 1);
    pard($debug_mode, "Debug mode: ");
    pard_end();
    
    // Update data files
    Entry_update_controller::update_entries(old_csv_filename:$data['previous'], current_csv_filename:$new_csv_filename);

    pard_sec("Other stuff");

    // Update i18n
    $cfg['log']->add("Updating I18n", 5);
    I18n::update();

    // Finish up
    // pard($import_report, "Parse report");
    // pard($dev_report, "Developer report");
    $cfg['log']->add_report($import_report, "Import Report");
    $cfg['log']->add_report($dev_report, "Developer Report");
    $cfg['log']->add("Script complete", 5);
    $cfg['log']->email_log($cfg);
    yaml_emit_file(DATA_FILENAME, ['previous'=>$new_csv_filename]);

    
    pard_end();
}
catch (Throwable $t) {
    pard_print_throwable($t);
}
pard_app_finished();