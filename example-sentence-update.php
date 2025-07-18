<?php
require_once("helpers/partial_debugger.php");

define("UNICODE_LDQOU", "\u{201C}");

global $examples, $globasa_index;



pard_app_start(true);
pard_sec("Initiating script");
pard('Load config-sentences.yaml');
$cfg = yaml_parse_file('config-sentences.yaml');
pard("load Globasa index");
$globasa_index = yaml_parse_file($cfg['globasa-index']);
$examples = [];
pard_end("initiation complete");


load_examples();
write_examples();
pard_app_finished();


/**
 * Loads candidate examples sentences from all sources.
 */
function load_examples(): void {
    global $cfg;
    pard_sec('Process input files');
    pard('load list of documents');
    $source_files = file($cfg['doxo-documents'],  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    pard_progress_start(count($source_files), "Processing source markdown documents");
    foreach($source_files as $filename) {
        sleep(1);
        parse_markdown_sources($filename);
        pard_progress_increment();
    }
    pard_progress_end("Processed");
    pard_end("Processed all files");
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
                // pard(substr($para, 0, 70), 'skipped: ');
                continue;
            }


        if (str_contains($para, "<")) {
            // FIXME: don't just ignore these lines
            // used for poetry
            pard($para, 'poetry? ');
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
    // pard("Word count: ".count($examples));
    
}


/**
 * Write out example API files for each term.
 */
function write_examples(): void {
    global $cfg, $examples;
    pard_sec('writing files');
    // pard("skipping"); // DEBUG
    pard_progress_start(count($examples), 'Writing examples for each word');
    
    foreach($examples as $slug => $examples) {
        usleep(100000);
        yaml_emit_file($cfg['api_path'].'/examples/'.$slug.'.yaml', $examples);
        pard_progress_increment();
    }
    pard_progress_end();
    pard_end("wrote all files");
}
