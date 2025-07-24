<?php
/**
 * Parse example sentences
 * 
 * Takes in sources along with the language index, creates an index on every
 * found term, with examples passages organized by priority as an int.
 * 
 *  1. Override passages
 *  2. Curated passages
 *  3. Full-text extracts
 * 
 * It outputs an example sentence YAML in format:
 * 1:
 *  -
 *   text: "To sen yukwe, na xorkone yu."
 *   cite: "Common Phrases and Expressions"
 *  -
 *   text: "Triunfayen sen royayen hu da nilwatu teslimu."
 *   cite: "Nelson Mandela"
 *  -
 *   text: "Moy insan xencu huru ji egal fe sungen ji haki."
 *   cite:
 *    eng: "Universal Declaration of Human Rights"
 *    fre: "Déclaration universelle des droits de l'homme"
 *    spa: "Declaración Universal de Derechos Humanos"
 * 2:
 *  ...
 * 3:
 *  ...
 */
declare(strict_types=1);
ini_set('log_errors', 1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once("helpers/partial_debugger.php");
pard\app_start(true);

function customExceptionHandler (\Throwable $e){
    if (filter_var(ini_get('display_errors'),FILTER_VALIDATE_BOOLEAN)) {
        pard\print_throwable($e);
    } else {
        error_log($e->getMessage());
        // echo "<h1>500 Internal Server Error</h1>";
    }
    exit;
}

set_exception_handler('customExceptionHandler');

set_error_handler(function ($level, $message, $file = '', $line = 0){
    throw new ErrorException($message, 0, $level, $file, $line);
});

register_shutdown_function(function (){
    $error = error_get_last();
    // Handle as exception if there was an error
    if ($error !== null) {
        $e = new ErrorException(
            $error['message'], 0, $error['type'], $error['file'], $error['line']
        );
        customExceptionHandler($e);
    }
});




define("UNICODE_LDQOU", "\u{201C}");

global $examples, $globasa_index;

pard\sec("Initiating script");
pard\m('Load config-sentences.yaml');
$cfg = yaml_parse_file('config-sentences.yaml');
pard\m("load Globasa index");
$globasa_index = yaml_parse_file($cfg['globasa-index']);
$examples = [];
pard\end("initiation complete");


load_examples();
write_examples();
pard\app_finished();


/**
 * Loads candidate examples sentences from all sources.
 */
function load_examples(): void {
    global $cfg;
    pard\sec('Process input files');
    pard\m('load list of documents');
    $source_files = file($cfg['doxo-documents'],  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    pard\progress_start(count($source_files), "Processing source markdown documents");
    foreach($source_files as $filename) {
        sleep(1);
        parse_markdown_sources($filename);
        pard\progress_increment();
    }
    pard\progress_end("Processed");
    pard\end("Processed all files");
}


/**
 * Loads candidates example sentences from markdown documents.
 * Ignore works not in Globasa index, skipping any English language terms.
 * 
 * @param string $filename  file to open
*/
function parse_markdown_sources(string $filename) {
    global $examples, $globasa_index;

    // Load file to array, each paragraph it's own element
    $content = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Continue until finding two front matter dividers
    $in_metadata = 2;
    foreach($content as $para) {
        /**
         * Skip metadata section.
         */
        if ($in_metadata) {
            if ($para ==='---') $in_metadata--;
            continue;
        }

        $para = trim($para);
        $first_char = mb_substr($para, 0, 1);
        if (
                !ctype_alpha($first_char) && !in_array($first_char,["'", '"', UNICODE_LDQOU, '>'])
                // $para==='---' || $para==='###' || $para==='+++' || $para==='<!-- -->' ||
                // $para==='<audio controls>' || $para==='</audio>' ||
                // $para==='<p>Your user agent does not support the HTML5 Audio element.</p>' ||
                // $para=='</div>' || // this one doesn't work if it's not triple ===
                // $para===''
                // str_starts_with($para, '<source ') || str_starts_with($para, '<div style="display: table;') ||
                // str_starts_with($para, '<iframe') || str_starts_with($para, '<img src=') ||
                // str_starts_with($para, '<p class="legal">') 
                // str_starts_with($para, '<')
                // str_starts_with($para, '') ||
            ) {
                // pard\m(substr($para, 0, 70), 'skipped: ');
                continue;
            }


        if (str_contains($para, "<")) {
            // FIXME: don't just ignore these lines
            // used for poetry
            pard\m($para, 'poetry? ');
            continue;
        }

        // Split paragraph in to sentences
        $sentences = preg_split('/(?<=[.?!;:])\s+/', $para, -1, PREG_SPLIT_NO_EMPTY);

        foreach($sentences as $sentence) {
            // Remove all punctuation
            $data = trim(preg_replace("#[[:punct:]]#", "", $sentence));

            foreach(explode(" ", $data) as $word) {
                
                // Skip if it's empty, has no letters or is not a valid word
                if (
                    empty($word) ||
                    !preg_match("/[a-z]/i", $word) ||
                    !array_key_exists($word, $globasa_index)
                ) {
                    continue;
                }
    
                $word = strtolower($word);
                $examples[$word][] = $sentence;
            }
        }
    }
    // pard\m("Word count: ".count($examples));
    
}


/**
 * Write out example API files for each term.
 */
function write_examples(): void {
    global $cfg, $examples;
    pard\sec('writing files');
    // pard\m("skipping"); // DEBUG
    pard\progress_start(count($examples), 'Writing examples for each word');
    
    foreach($examples as $slug => $examples) {
        usleep(100000);
        yaml_emit_file($cfg['api_path'].'/examples/'.$slug.'.yaml', $examples);
        pard\progress_increment();
    }
    pard\progress_end();
    pard\end("wrote all files");
}
