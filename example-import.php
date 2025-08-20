<?php

/**
 * Parse example sentences
 * 
 * Takes in source documents and parses content to create sentences
 * listing for each term.
 * 
 * Limitations: homonyms, and with auxilary documents english words
 * that are also in the worldlang index.
 */

declare(strict_types=1);

namespace worldlang\examples;

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

/*************************************
 * Exceptional Error Handling
 * ***********************************
 */
ini_set('log_errors', 1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once("helpers/partial_debugger.php");
\pard\app_start(true);

function customExceptionHandler(\Throwable $e)
{
    if (filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN)) {
        \pard\print_throwable($e);
    } else {
        error_log($e->getMessage());
    }
}

set_exception_handler('worldlang\examples\customExceptionHandler');

set_error_handler(function ($level, $message, $file = '', $line = 0) {
    throw new \ErrorException($message, 0, $level, $file, $line);
});

register_shutdown_function(function () {
    $error = error_get_last();
    // Handle as exception if there was an error
    if ($error !== null) {
        $e = new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );
        customExceptionHandler($e);
    }
});


/*******************************
 * INIT
 * *****************************
*/

define("OVERRIDE_PRIORITY", 1);
define("CURATED_PASS_PRIORITY", 2);
define("CURATED_DOCS_PRIORITY", 3);
define("AUXILARY_PRIORITY", 4);

define("UNICODE_LDQOU", "\u{201C}");

require_once("vendor/parsedown/Parsedown.php");

global $examples, $wld_index, $pd;



\pard\sec("Initiating script");
$pd = new \Parsedown();
\pard\m('Load configuration');
$cfg = yaml_parse_file('config-example.yaml');
\pard\m("load term index");
$wld_index = yaml_parse_file($cfg['worldlang_index']);
$examples = [];
\pard\end("initiation complete");


/**************************
 * Start
 * ************************
 */
example_sentences();
\pard\app_finished();



/**
 * Take example and add it to all $terms.
 */
function add_examples(string $e, array $terms, array $c, int $p)
{
    global $examples, $wld_index, $pd;

    array_unique($terms);
    $e = $pd->line(mb_trim($e));
    
    foreach ($terms as $t) {

        $t = strtolower($t);
        if (!array_key_exists($t, $wld_index))
            continue;

        $examples[$t][$p][] = [
            'text' => $e,
            'cite' => $c,
        ];
    }
}



/**
 * Process example source data and generate
 * dictionary example data output.
 */
function example_sentences(): void
{
    global $cfg;
    global $examples;

    \pard\sec("Make example files");
    process_passage_sources($cfg['source_data']['override_passages'], OVERRIDE_PRIORITY);
    process_passage_sources($cfg['source_data']['curated_passages'], CURATED_PASS_PRIORITY);
    process_docs_sources($cfg['source_data']['curated_docs'], CURATED_DOCS_PRIORITY);
    process_aux_sources($cfg['source_data']['auxilary_files'], "Doxo", AUXILARY_PRIORITY);
    write_examples();
    \pard\end("Done");
}



/**
 * Scans auxilary markdown documents for worldlang terms, add to `$examples` global.
 * Assumes document formatted for Grav.
 */
function parse_aux_source(array $source, string $title_prefix, int $priority): void
{
    update_citations($source, $title_prefix);

    // Load file to array, each paragraph it's own element
    $first_lang = array_key_first($source['file']);
    $fp = fopen($source['file'][$first_lang], 'r');
    if (!$fp) {
        \pard\m($source[$first_lang]['file'], "Open fail", true);
        return;
    }

    // skip metadata
    $in_metadata = 2;
    while ($in_metadata && (($line = fgets($fp)) !== false)) {
        $line = mb_trim($line);
        if (strcmp($line, "---") === 0 || strcmp($line, "+++") === 0) $in_metadata--;
    }

    parse_markdown_filestream($fp, $source['cite'], $priority);
    fclose($fp);
}



/**
 * Scans curated markdown document for worldlang terms, add to `$examples` global.
 */
function parse_document(string $source, int $p): void
{
    $fp = fopen($source, 'r');
    if (!$fp) {
        \pard\m($source['file'], "Open fail", true);
        return;
    }

    // Scan thru metadata
    $in_metadata = 2;
    $meta = "";
    while ($in_metadata && (($line = fgets($fp)) !== false)) {
        if (strcmp($line, "---\r\n") === 0 || strcmp($line, "---\r\n") === 0) $in_metadata--;
        else $meta .= $line;
    }
    $meta = yaml_parse($meta);

    parse_markdown_filestream($fp, $meta['cite'], $p);
    fclose($fp);
}



/*
 * Parses filestream line by line, isolating words and adding them.
 */
function parse_markdown_filestream($fp, array $c, int $p): void
{
    while (($line = fgets($fp)) !== false) {
        $line = mb_trim($line);
        $first_char = mb_substr($line, 0, 1);
        /* Skip if '---' '###' '+++' '<!-- -->' '<audio' '</audio>' '<p>Your user agent' '</div>'
           '<source ' '<div style="display: table;') '<iframe' '<img src=' '<p class="legal">') */
        if (!\IntlChar::isalpha($first_char) && !in_array($first_char, ["'", '"', UNICODE_LDQOU, '>']))
            continue;

        /* Strip out markdown quote formatting */
        if (str_starts_with($line, '>')) {
            $line = mb_substr($line, 1);
        }

        if (str_contains($line, "<")) {
            // FIXME: don't just ignore these lines
            // used for poetry???
            \pard\m($line, 'poetry? ');
            continue;
        }

        // Split paragraph in to sentences
        $sentences = preg_split('/(?<=[.?!;:])\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($sentences as $s) {
            parse_sentence($s, $c, $p);
        }
    }
}



/**
 * Process passages data.
 */
function parse_passages(string $source, int $priority): void
{

    $data = yaml_parse_file($source);
    foreach ($data as $passage) {
        add_examples($passage['text'], $passage['terms'], $passage['cite'], $priority);
    }
}



/**
 * Parses a worldlang sentence and adds the sentence as
 * and examples sentence for each term present.
 */
function parse_sentence(string $s, array $c, int $p)
{
    // Remove all punctuation
    $data = mb_trim(preg_replace("/[[:punct:]]/u", "", $s));
    add_examples($s, explode(" ", $data), $c, $p);
}



/**
 * Process markdown documents developed for Grav.
 */
function process_aux_sources(string $aux_sources_file, string $title_prefix, int $priority): void
{
    $source_files = yaml_parse_file($aux_sources_file);
    \pard\progress_start(count($source_files), "Loading aux files with priority {$priority}");
    foreach ($source_files as $data) {
        usleep(150000);
        parse_aux_source($data, $title_prefix, $priority);
        \pard\progress_increment();
    }
    \pard\progress_end("Files loaded");
}



/**
 * Process document with proper citation.
 */
function process_docs_sources(string $corpus_sources_file, int $priority): void
{
    $source_files = yaml_parse_file($corpus_sources_file);
    \pard\progress_start(count($source_files), "Processing curated markdown documents");
    foreach ($source_files as $data) {
        usleep(150000);
        parse_document($data, $priority);
        \pard\progress_increment();
    }
    \pard\progress_end("Files loaded");
}



/**
 * Processes all sources in provided source file.
 */
function process_passage_sources(string $source_data, int $priority)
{
    $source_files = yaml_parse_file($source_data);
    \pard\progress_start(count($source_files), "Loading passages with priority {$priority}");
    foreach ($source_files as $file) {
        usleep(150000);
        parse_passages($file, $priority);
        \pard\progress_increment();
    }
    \pard\progress_end("Files loaded");
}



/**
 * Adds citation data to the provided sources array.
 */
function update_citations(array &$sources, string $title_prefix)
{
    foreach ($sources['file'] as $lang => $filename) {
        // Load file to array, each paragraph it's own element
        $fp = fopen($filename, 'r');
        if (!$fp) {
            \pard\m($filename, "Open fail", true);
            return;
        }

        // Scan thru metadata
        $in_metadata = 2;
        while ($in_metadata) {
            $line = fgets($fp);
            if ($line === false) break;
            if (strcmp(trim($line), "---") === 0) $in_metadata--;
            if (str_starts_with($line, 'title:')) {
                $i = strpos($line, "'");
                if ($i !== false) {
                    // Cut out what is within quotes
                    $i += 1;
                    $j = strrpos($line, "'") - $i;
                } else {
                    // Remove identifier
                    $i = 7;
                    $j = null;
                }
                $sources['cite']['text'][$lang] = $title_prefix . ' ' . mb_trim(str_replace("''", "'", mb_substr($line, $i, $j)));
                break;
            }
        }
        fclose($fp);
    }
}



/**
 * Write out example API files for each term.
 */
function write_examples(): void
{
    global $cfg, $examples;
    \pard\progress_start(count($examples), 'Writing examples for each entry');

    if (!file_exists($cfg['examples_output'])) {
		mkdir($cfg['examples_output'], 0744);
	}

    foreach ($examples as $slug => $data) {
        usleep(20000);
        yaml_emit_file($cfg['examples_output'] . "{$slug}.yaml", $data);
        \pard\progress_increment();
    }
    \pard\progress_end("Files written");
}