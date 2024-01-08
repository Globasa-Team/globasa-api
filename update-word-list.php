<?php
namespace globasa_api;

use Throwable;

$parsed_entries = [];
$min_entries = [];
$basic_entries = [];
$term_indexes = [];
$search_terms = [];
$tags = [];
$backlinks = [];
$natlang_etymologies = [];
$word_count = 0;
$lang_count = [];
$category_count = [];
$debug_data = [];

try {
    // Startup
    $app_path = __DIR__;
    require_once("{$app_path}/init.php");

    if ($argv[1] === 'r') {
        $new_csv_filename = $data['previous'];
        $c['log']->add("Reprocessing previous CSV file");
    } else {
        $new_csv_filename = $argv[1];
    }


    $c['log']->add("Using new: " . $new_csv_filename, 1);
    $c['log']->add("Using old: " . $data['previous'], 1);
    $c['log']->add("Environment: " . ($c['dev'] ? 'dev' : 'production'), 1);
    
    // Update data files
    $c['log']->add("Loading old CSV", 5);
    $old_data = load_csv($data['previous']);
    $c['log']->add("Loading current terms", 5);
    $csv_data = Word_list::load_current_terms(
        current_csv_filename:$new_csv_filename,

        parsed_entries:$parsed_entries,

        min_entries:$min_entries,
        basic_entries:$basic_entries,
        
        term_indexes:$term_indexes,
        search_terms:$search_terms,
        tags:$tags,
        
        natlang_etymologies:$natlang_etymologies,
        
        word_count:$word_count,
        lang_count:$lang_count,
        category_count:$category_count,
        
        debug_data:$debug_data,
        c:$c
    );
    $c['log']->add("Logging changes", 5);
    Word_list::log_changes($csv_data, $old_data, $c);

    // Write dictionary files
    $c['log']->add("Writting files", 5);
    File_controller::write_api2_files(
        parsed_entries:$parsed_entries,

        min_entries:$min_entries,
        basic_entries:$basic_entries,
        
        term_indexes:$term_indexes,
        search_terms:$search_terms,
        tags:$tags,
        
        natlang_etymologies:$natlang_etymologies,
        
        word_count:$word_count,
        lang_count:$lang_count,
        category_count:$category_count,
        
        config:$c
    );



    // Update i18n
    $c['log']->add("Updating I18n", 5);
    I18n::update($c);

    // Finish up
    $c['log']->add("Script complete", 5);
    $c['log']->email_log($c);
    yaml_emit_file(DATA_FILENAME, ['previous'=>$argv[1]]);
}
catch (Throwable $e) {
    echo("\nCAUGHT
    Prob\t".$e->getCode()." :".$e->getMessage().PHP_EOL.
        "Line\t".$e->getLine()." :".$e->getFile().PHP_EOL);
}