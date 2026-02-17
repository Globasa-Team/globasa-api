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

define('FIND_T_BRACKET_REGEX', '/<.*?>/');

enum Markdown_type
{
    case grav;
    case wld;
}

enum Sentence_state
{
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


/**
 * INIT
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

define("SLEEP_1SEC", 1000000);
define("SLEEP_200MS", 200000);
define("SLEEP_100MS", 100000);
define("SLEEP_50MS",   50000);


require_once("vendor/parsedown/Parsedown.php");

global $examples, $wld_index, $pd;



/**
 * Take example and add it to all $terms.
 */
function add_example(string $e, array $c,)
{
    global $examples, $wld_index, $pd;

    $e = fix_sentence_quotes(mb_trim($e));
    [$segments, $terms] = break_apart_example($e);
    $e = $pd->line($e);

    foreach ($terms as $t) {
        if (!array_key_exists($t, $wld_index))
            continue;

        $examples[$t][] = [
            'text' => $e,
            'cite' => $c,
            'translations' => $segments,
        ];
    }
}



/**
 * Split example sentence in to word/nonword segments.
 * Find all terms. Return segments and terms.
 */
function break_apart_example(string $e): array
{
    global $wld_index;

    $segments = split_sentence($e);
    $terms = array();
    foreach ($segments as $cur) {
        $term = mb_strtolower($cur['text']);
        if (key_exists($term, $wld_index)) {
            $terms[] = $term;
        }
    }
    $term = array_unique($terms);

    return [$segments, $terms];
}



/**
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
function fix_sentence_quotes(string $text): string
{
    $quotes = array();
    $chars = mb_str_split($text);
    $result = $text;

    for ($i = 0; $i < count($chars); $i++) {
        $c = $chars[$i];
        $closing_quote = false;
        $opening_quote = false;

        if (
            ctype_alpha($c) || $c === ' ' || $c === '.' || $c === '?' ||
            $c === ':' || $c === '!' || $c === ',' || $c === ';'
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

    \pard\sec("Make example files");
    \pard\progress_start(count($cfg['source_files']), "Loading files");
    foreach ($cfg['source_files'] as $source) {
        if ($source['type'] === 'passage')
            import_passages($source);
        if ($source['type'] === 'doc_md')
            import_md_document($source, Markdown_type::wld);
        if ($source['type'] === 'aux_grav')
            import_md_document($source, Markdown_type::grav);
        \pard\progress_increment();
        usleep(SLEEP_100MS);
    }
    \pard\progress_end("Files loaded");
    write_examples();
    \pard\end("Done");
}



/**
 * Loads metadata from Markdown files with custome worldlang format metadata or
 * grave metadata. Scans markdown document for worldlang terms, add to
 * `$examples` global.
 */
function import_md_document(array $source, Markdown_type $type): void
{
    if ($type === Markdown_type::wld) {
        $fp = fopen($source['file'], 'r');
        if (!$fp) {
            \pard\m($source['file'], "Open fail", true);
            return;
        }
        $meta = read_md_frontmatter($fp);
        $source['cite'] = $meta['cite'];
    } else if ($type === Markdown_type::grav) {
        $first_lang = array_key_first($source['file']);
        $fp = fopen($source['file'][$first_lang], 'r');
        if (!$fp) {
            \pard\m($source['file'][$first_lang], "Open fail", true);
            return;
        }
        read_md_frontmatter($fp); // skip metadata
        update_citations($source);
        if (!key_exists('cite', $source) || !key_exists('text', $source['cite'])) {
            m($source, "missing citation", true);
        }
    }
    parse_markdown_filestream($fp, $source['cite']);
    fclose($fp);
}



/**
 * Process passages data.
 */
function import_passages(array $source): void
{
    $data = yaml_parse_file($source['file']);
    foreach ($data as $passage) {
        parse_paragraph($passage['text'], $passage['cite']);
    }
}



/**
 * Parses filestream line by line, isolating words and adding them.
 */
function parse_markdown_filestream($fp, array $c): void
{
    while (($line = fgets($fp)) !== false) {
        $line = mb_trim($line);
        $first_char = mb_substr($line, 0, 1);
        /* Skip if '---' '###' '+++' '<!-- -->' '<audio' '</audio>' '<p>Your user agent' '</div>'
           '<source ' '<div style="display: table;') '<iframe' '<img src=' '<p class="legal">') */
        if (!\IntlChar::isalpha($first_char) && !in_array($first_char, ["'", '"', UNICODE_LDQOU, '>']))
            continue;

        /* Strip out markdown block quote formatting */
        if (str_starts_with($line, '>')) {
            $line = mb_substr($line, 1);
        }

        if (str_contains($line, "<")) {
            // FIXME: don't just ignore these lines
            // used for poetry???
            \pard\m($line, 'poetry? ');
            continue;
        }
        parse_paragraph($line, $c);
    }
}



/**
 * Break aparent $para by end of sentence punctuation not semicolons.
 * End of sentence may be followed by quote.
 * 
 * Capture setence segment, and then end of sentence.
 */
function parse_paragraph(string $para, array $c)
{
    $split = preg_split('/([.?!].?)\s/u', $para, 0, PREG_SPLIT_DELIM_CAPTURE);
    $sentence_itr = new \ArrayObject($split)->getIterator();

    while ($sentence_itr->valid()) {
        // Get sentence words
        $sentence = $sentence_itr->current();
        $sentence_itr->next();
        // Get punctuation
        if ($sentence_itr->valid()) {
            $sentence .= $sentence_itr->current();
            $sentence_itr->next();
        }
        add_example($sentence, $c);
    }
}



/**
 * Scans through the frontmatter and returns it as an array.
 */
function read_md_frontmatter($fp): array
{
    // Scan thru metadata, skipping opening metadata line
    $first_line = fgets($fp);
    // m($first_line, 'skipped line'); // DEBUG
    $found_end = false;
    $metadata = "";
    while (!$found_end && (($line = fgets($fp)) !== false)) {
        if ($line === "---\r\n" || $line === "+++\r\n" || $line === "---\n" || $line === "+++\n")
            $found_end = true;
        else $metadata .= $line;
    }
    // m($found_end, "found end?"); // DEBUG
    // m($metadata, 'metadata'); // DEBUG
    return yaml_parse($metadata);
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
 *  [`'`, `blimey`, `', '`, `yup`, `'`]
 */
function split_sentence(string $e): array
{
    $ret = array();

    // find all characters/graphemes
    $data = grapheme_str_split($e);
    $count = count($data);

    $cur = ""; // current segment run
    $type = null; // segment type
    $word_part = null;

    for ($i = 0; $i < $count; $i++) {
        // Determine if this is a letter or part of a word (like apos or rqou)
        if (IntlChar::isalpha($data[$i]) || ($data[$i] !== ' ' && $word_part && $i + 1 < $count && IntlChar::isalpha($data[$i + 1]))) {
            $word_part = true;
            if ($type == null) $type = Sentence_state::Word;
        } else {
            $word_part = false;
            if ($type == null) $type = Sentence_state::Nonword;
        }

        if ((!$word_part && $type == Sentence_state::Word) || ($word_part && $type == Sentence_state::Nonword)) {
            // This starts a new segment (quote or space or other)
            $ret[]['text'] = $cur;
            $cur = "";
            if ($type == Sentence_state::Word) $type = Sentence_state::Nonword;
            else $type = Sentence_state::Word;
        }

        $cur .= $data[$i];
    }

    if ($cur !== "") $ret[] = ['text' => $cur];

    return $ret;
}



/**
 * Adds citation data for each language to the provided sources array.
 */
function update_citations(array &$source)
{
    foreach ($source['file'] as $lang => $filename) {
        // Load file to array, each paragraph it's own element
        $fp = fopen($filename, 'r');
        if (!$fp) {
            \pard\m($filename, "Open fail", true);
            continue;
        }
        $meta = read_md_frontmatter($fp);
        $source['cite']['text'][$lang] = $source['title_prefix'] . ': ' . $meta['title'];
        fclose($fp);
    }
}



/**
 * Write out example API files for each term.
 */
function write_examples(): void
{
    global $cfg, $examples;

    if (!file_exists($cfg['examples_output'])) {
        mkdir($cfg['examples_output'], 0744);
    }

    $dict = array();
    \pard\progress_start(count($cfg['langs']), 'Loading dictionaries');
    foreach ($cfg['langs'] as $lang) {
        $dict[$lang] = yaml_parse_file($cfg['dict_template'] . $lang . '.yaml');
        sleep(1);
        \pard\progress_increment();
    }
    \pard\progress_end("Loaded dictionaries");

    \pard\progress_start(count($examples), 'Writing examples for each entry');
    foreach ($examples as $slug => $entry_examples) {

        // Get translations for this entry's examples
        foreach ($entry_examples as $e_key => $e_data) {
            foreach ($e_data['translations'] as $segment_key => $segment_data) {
                $term = mb_strtolower($segment_data['text']);
                foreach ($cfg['langs'] as $lang) {
                    if (array_key_exists($term, $dict[$lang])) {
                        $entry_examples[$e_key]['translations'][$segment_key][$lang]
                            = preg_replace(
                                FIND_T_BRACKET_REGEX, '',
                                "{$term} ({$dict[$lang][$term]['class']}) {$dict[$lang][$term]['translation']}");
                    }
                }
            }
        }

        yaml_emit_file($cfg['examples_output'] . "{$slug}.yaml", $entry_examples);
        \pard\progress_increment();
        usleep(SLEEP_100MS);
    }
    \pard\progress_end("Files written");
}
