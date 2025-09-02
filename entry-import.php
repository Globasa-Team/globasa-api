<?php
// declare(strict_types=1);
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

    $verbose_mode,
    $write_files,

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

$comparison_option = true;
$verbose_mode = false;
$write_files = true;

$debug_data = [];
$debug_mode = false;

// Initialize local data
$new_csv_filename = "";

$app_path = __DIR__;
require_once("{$app_path}/init-entry.php");
$opt = getopt(OPT_SHORT, OPT_LONG);

// Startup
if (key_exists('v', $opt)) {
    $verbose_mode = true;
}

\pard\app_start($verbose_mode);
\pard\sec("Initiation");
\pard\m('Parsing parameters');
\pard\m($verbose_mode, "Option verbose");

if (key_exists('d', $opt)) {
    $debug_mode = true;
    \pard\m("only writing some entries", "Option debug");
}
if (key_exists('p', $opt) && isset($cfg['api_path_production'])) {
    \pard\m("Using output from last API run", "Option input");
    $new_csv_filename = $cfg['api_path_production'] . "/word-list.csv";
}
if (key_exists('a', $opt)) {
    \pard\m("Using output from last API run", "Option input");
    $new_csv_filename = $cfg['api_path'] . "/word-list.csv";
}
if (key_exists('l', $opt)) {
    \pard\m("Reprocessing last CSV file", 'Option input');
    $new_csv_filename = $data['previous'];
    $comparison_option = false;
}
if (key_exists('c', $opt)) {
    \pard\m("Skipping old CSV comparison", 'Option');
    $comparison_option = false;
}
if (key_exists('w', $opt)) {
    \pard\m("Skipping file writing", 'Option');
    $write_files = false;
}
if (key_exists('file', $opt)) {
    $new_csv_filename = $opt['file'];
}
\pard\m($new_csv_filename, "New CSV file");
if (empty($new_csv_filename)) {
    print("No input file specified\n");
    exit(0);
}

try {
    $cfg['log']->add("Environment: " . ($cfg['dev'] ? 'dev' : 'production'));
    \pard\m($debug_mode, "Debug mode");
    \pard\end();
    
    // Update data files
    Entry_update_controller::update_entries(old_csv_filename:$data['previous'], current_csv_filename:$new_csv_filename);

    \pard\sec("Other stuff");
    // Update i18n
    $cfg['log']->add("Updating I18n", 5);
    I18n::update();

    // Finish up
    \pard\print_array_inline($import_report, "Parse report");
    $cfg['log']->add_report($import_report, "Import Report");
    \pard\print_array_inline($dev_report, "Developer report");
    $cfg['log']->add_report($dev_report, "Developer Report");
    $cfg['log']->add("Script complete", 5);
    $cfg['log']->email_log($cfg);
    yaml_emit_file(DATA_FILENAME, ['previous'=>$new_csv_filename]);
    
    \pard\end();
} catch (Throwable $t) {
    \pard\print_throwable($t);
}
\pard\app_finished();