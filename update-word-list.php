<?php

namespace globasa_api;

use Throwable;

global
    $parse_report,
    $rhyme_data,
    $natlang_count,
    $standard_entries,
    $stats,
    $dict,
    $examples;



$parse_report = [];
$rhyme_data = [];
$natlang_count = [];
$standard_entries = [];
$stats = [];
$dict = [];

$min_entries = [];
$basic_entries = [];
$term_indexes = [];
$search_terms = [];
$tags = [];
$backlinks = [];
$natlang_etymologies = [];
$word_count = 0;
$category_count = [];
$debug_data = [];

$new_csv_filename = "";

try {
    // Startup
    $app_path = __DIR__;
    require_once("{$app_path}/init.php");
    pard_app_start();
    pard_sec("Initiation");

    if ($argv[1] === 'r') {
        $new_csv_filename = $data['previous'];
        $c['log']->add("Reprocessing previous CSV file", 1);
    } else {
        $new_csv_filename = $argv[1];
    }

    $examples = yaml_parse_file('examples.yaml');

    $c['log']->add("Using new: " . $new_csv_filename, 1);
    $c['log']->add("Using old: " . $data['previous'], 1);
    $c['log']->add("Environment: " . ($c['dev'] ? 'dev' : 'production'), 1);
    
    // Update data files
    $c['log']->add("Loading old CSV", 1);
    $old_data = load_csv($data['previous']);
    $c['log']->add("Loading current terms", 1);
    pard_end();
    $csv_data = Word_list::load_current_terms(
        current_csv_filename:$new_csv_filename,
        parsed_entries:$dict,
        min_entries:$min_entries,
        basic_entries:$basic_entries,
        term_indexes:$term_indexes,
        search_terms:$search_terms,
        tags:$tags,
        natlang_etymologies:$natlang_etymologies,
        word_count:$word_count,
        category_count:$category_count,
        debug_data:$debug_data,
        c:$c
    );

    pard_sec("Post dictionary");
    $c['log']->add("Logging changes", 2);
    Word_list::log_changes($csv_data, $old_data, $c);
    Word_list::calculate_stats();

    // Write dictionary files
    $c['log']->add("Writting files", 2);
    File_controller::write_api2_files(
        parsed_entries:$dict,

        min_entries:$min_entries,
        basic_entries:$basic_entries,
        
        term_indexes:$term_indexes,
        search_terms:$search_terms,
        tags:$tags,
        
        natlang_etymologies:$natlang_etymologies,
        
        word_count:$word_count,
        category_count:$category_count,
        
        config:$c
    );


    pard_sec("Other stuff");
    // Update i18n
    $c['log']->add("Updating I18n", 5);
    I18n::update($c);

    // Finish up
    $c['log']->add("Script complete", 5);
    $c['log']->email_log($c);
    yaml_emit_file(DATA_FILENAME, ['previous'=>$new_csv_filename]);
}
catch (Throwable $t) {
    echo("\nCAUGHT
    Prob\t".$t->getCode()." :".$t->getMessage().PHP_EOL.
        "Line\t".$t->getLine()." :".$t->getFile().PHP_EOL);
}