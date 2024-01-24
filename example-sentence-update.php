<?php

require_once("helpers/partial_debugger.php");

global $examples, $globasa_index;

pard_sec("Start update");

$cfg = yaml_parse_file('config-sentences.yaml');

$source_files = file($cfg['doxo-documents'],  FILE_IGNORE_NEW_LINES);
$globasa_index = yaml_parse_file($cfg['globasa-index']);
$examples = [];

pard_sec('loading files');

foreach($source_files as $filename) {
    pard($filename);
    update_examples_from_file($filename);
}

pard_sec('writing file');
yaml_emit_file('examples.yaml', $examples);

pard_sec("written");







function update_examples_from_file($filename) {
    global $examples, $globasa_index;

    // Load file to array, each paragraph it's own element
    $content = file($filename, FILE_IGNORE_NEW_LINES);

    // Continue until finding two front matter dividers
    $in_metadata = 2;
    foreach($content as $para) {
        /**
         * Skip metadata section.
         */
        if ($in_metadata) {

            if ($para !=='---') {
                continue;
            } else {
                $in_metadata--;
            }
        }

        $para = trim($para);
        if (
                $para==='---' ||
                $para==='###' ||
                $para==='<audio controls>' ||
                $para==='</audio>' ||
                $para==='<p>Your user agent does not support the HTML5 Audio element.</p>' ||
                $para=='</div>' || // this one doesn't work if it's not triple ===
                $para==='<!-- -->' ||
                // $para===''
                // $para===''
                // $para===''
                // $para===''
                // $para===''
                // $para===''
                str_starts_with($para, '<source ') ||
                str_starts_with($para, '<div style="display: table;') ||
                str_starts_with($para, '<iframe') ||
                str_starts_with($para, '<img src=') ||
                str_starts_with($para, '<p class="legal">') 
                // str_starts_with($para, '') ||
                // str_starts_with($para, '') ||
                // str_starts_with($para, '') ||
                // str_starts_with($para, '') ||
                // str_starts_with($para, '') ||
            ) {
                continue;
            }


        if (str_contains($para, "<")) {
            // FIXME: don't just ignore these lines
            // used for poetry
            // pard($para);
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
    pard("Word count: ".count($examples));
    sleep(1);
}