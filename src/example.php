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

namespace WorldlangDict\Examples;

use IntlChar;

use function pard\m;

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

enum Sentence_state {
    case Word;
    case Nonword;
}

/*************************************
 * Exceptional Error Handling
 * ***********************************
 */
ini_set('log_errors', 1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once("src/partial_debugger.php");

function customExceptionHandler(\Throwable $e)
{
    if (filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN)) {
        \pard\print_throwable($e);
    } else {
        error_log($e->getMessage());
    }
}

set_exception_handler('WorldlangDict\Examples\customExceptionHandler');

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
define("UNICODE_RDQOU", "\u{201D}");
define("UNICODE_LSQOU", "\u{2018}");
define("UNICODE_RSQOU", "\u{2019}");
define("UNICODE_DQOU", "\u{0022}");
define("UNICODE_SQOU", "\u{0027}");

require_once("vendor/parsedown/Parsedown.php");

global $examples, $wld_index, $pd;


/**
 * Take example and add it to all $terms.
 */
function add_example(string $e, array $terms, array $c, int $p)
{
    global $examples, $wld_index, $pd;

    $terms = array_unique(array_map('strtolower', $terms));
    $e = fix_quotes(mb_trim($e));
    $e = $pd->line($e);
    $translation_data = example_translation_data($e, $terms);

    foreach ($terms as $t) {

        $t = strtolower($t);
        if (!array_key_exists($t, $wld_index))
            continue;

        $examples[$t][$p][] = [
            'text' => $e,
            'cite' => $c,
            // 'translation' => $translation_data,
        ];
    }
}



/**
 * fix_quotes
 * 
 * Analyzes the text string multibyte character by character, adding opening
 * quotes to the `$quotes` stack, and popping quotes quotes off when finding
 * a closing quote. Quotes within text (apostrophies) are ignored. Quotes
 * surrounded by white space are ignored.
 * 
 * When an left quote is found, add it. When a closing double quote is
 * found, pop the stack. When a basic ', " or left single quote is found,
 * use whitespace to determine if it's an apostrophy, closing quote or
 * opening quote.
 * 
 * If poping the stack returns a null, add an opening quote to the start,
 * corresponding to the currently found closing quote.
 * 
 * If loop exits with items on the stack, put the corresponding closing quote
 * on.
 * 
 */
function fix_quotes(string $text): string
{
    $quotes = array();
    $chars = mb_str_split($text);
    $result = $text;

    for ($i = 0; $i < count($chars); $i++) {
        $c = $chars[$i];
        $closing_quote = false;
        $opening_quote = false;

        if (
                ctype_alpha($c) || $c === ' ' || $c === '.' || $c === '?'|| $c === ':' ||
                $c === '!' || $c === ',' || $c === ';'
            ) continue;

        if ($c === UNICODE_RSQOU || $c === UNICODE_SQOU || $c === UNICODE_DQOU) {

            if ($i == 0 || ctype_space($chars[$i - 1]))
                $space_before = true;
            else $space_before = false;

            if (($i >= count($chars) - 1) || ctype_space($chars[$i + 1]))
                $space_after = true;
            else $space_after = false;

            if (!$space_before && $space_after)
                $closing_quote = true;
            if ($space_before && !$space_after)
                $opening_quote = true;
        }

        if ($c === UNICODE_LDQOU || $c === UNICODE_LSQOU || $opening_quote) {
            array_push($quotes, $c);
        }
        if ($c === UNICODE_RDQOU || $closing_quote) {
            $q = array_pop($quotes);
            if ($q === null) {
                if ($c === UNICODE_SQOU) {
                    $result = UNICODE_SQOU . $result;
                } elseif ($c === UNICODE_DQOU) {
                    $result = UNICODE_DQOU . $result;
                } elseif ($c === UNICODE_RSQOU) {
                    $result = UNICODE_LSQOU . $result;
                } elseif ($c === UNICODE_RDQOU) {
                    $result = UNICODE_LDQOU . $result;
                }
            }
        }
    }

    // Add missing right quotes
    while (count($quotes)) {
        $q = array_pop($quotes);
        if ($q === UNICODE_SQOU) $result = $result . UNICODE_SQOU;
        elseif ($q === UNICODE_DQOU) $result = $result . UNICODE_DQOU;
        elseif ($q === UNICODE_LSQOU) $result = $result . UNICODE_RSQOU;
        elseif ($q === UNICODE_LDQOU) $result = $result . UNICODE_RDQOU;
    }

    return $result;
}



/**
 * Process example source data and generate
 * dictionary example data output.
 */
function import_example_sentences(): void
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

        parse_paragraph($line, $c, $p);
    }
}



/**
 * Break aparent $para by punctuation
 * 
 * Not semicolons
 */
function parse_paragraph(string $para, array $c, int $pri)
{
    $split = preg_split('/([.?!].?)\s/u', $para, 0, PREG_SPLIT_DELIM_CAPTURE);
    $itr = new \ArrayObject($split)->getIterator();

    while ($itr->valid()) {
        $sentence = $itr->current();
        $itr->next();
        if ($itr->valid()) {
            $sentence .= $itr->current();
            $itr->next();
        }
        parse_sentence($sentence, $c, $pri);
    }
}



/**
 * Process passages data.
 */
function parse_passages(string $source, int $priority): void
{

    $data = yaml_parse_file($source);
    foreach ($data as $passage) {
        add_example($passage['text'], $passage['terms'], $passage['cite'], $priority);
    }
}



/**
 * Parses a worldlang sentence and adds the sentence as
 * and examples sentence for each term present.
 */
function parse_sentence(string $s, array $c, int $p)
{
    // Remove all punctuation
    $data = explode(" ", mb_trim(preg_replace("/[[:punct:]]/u", "", $s)));
    add_example($s, $data, $c, $p);
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
                $sources['cite']['text'][$lang] = $title_prefix . ': ' . mb_trim(str_replace("''", "'", mb_substr($line, $i, $j)));
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


function example_translation_data(string $e, array $terms): array
{
    $ret = array();
    var_dump($e);
    return $ret;
}


/**
 * Isolate words from non-words.
 * 
 * Builds the the sentence up by putting together segments. A segment
 * is a run of either alpha or non-alpha characters. So:
 * 
 *  'blimey', 'yup'
 * 
 * is broken down to 4 segments:
 *  '
 *  blimey
 *  ' '
 *  yup
 *  '
 * 
 * 
 */
function split_sentence(string $e): array
{
    $ret = array();

    // find all characters/graphemes
    $data = grapheme_str_split($e);
    $count = count($data);

    $cur = ""; // current segment run
    $type = null; // segment type

    for ($i = 0; $i < $count; $i++) {
        // Determine if this is a letter or part of a word (like apos or rqou)
        if (IntlChar::isalpha($data[$i]) || ($word_part && $i + 1 < $count && IntlChar::isalpha($data[$i+1]) ) ) {
            $word_part = true;
            if ($type == null) $type = Sentence_state::Word;
        } else {
            $word_part = false;
            if ($type == null) $type = Sentence_state::Nonword;
        }

        if ( (!$word_part && $type == Sentence_state::Word) || ($word_part && $type == Sentence_state::Nonword) ) {
            // This starts a new segment (quote or space or other)
            $ret[] = $cur;
            $cur = "";
            $type = null;
        }

        $cur .= $data[$i];
    }

    if ($cur !== "") $ret[] = ['text'=>$cur];

    return $ret;
}