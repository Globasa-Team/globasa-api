<?php

require_once("helpers/partial_debugger.php");

pard_sec("Start update");

$cfg = yaml_parse_file('config-sentences.yaml');

$source_files = file($cfg['doxo-documents'],  FILE_IGNORE_NEW_LINES);

foreach($source_files as $filename) {
    pard_sec($filename);
    update_examples_from_file($filename);
}


function update_examples_from_file($filename) {
    $content = file($filename, FILE_IGNORE_NEW_LINES);

    $in_metadata = 2;
    foreach($content as $line) {

        /**
         * Skip metadata section.
         */
        if ($in_metadata) {

            if ($line !=='---') {
                continue;
            } else {
                $in_metadata--;
            }
        }

        $line = trim($line);
        if (
                $line==='---' ||
                $line==='###' ||
                $line==='<audio controls>' ||
                $line==='</audio>' ||
                $line==='<p>Your user agent does not support the HTML5 Audio element.</p>' ||
                $line=='</div>' || // this one doesn't work if it's not triple ===
                $line==='<!-- -->' ||
                // $line===''
                // $line===''
                // $line===''
                // $line===''
                // $line===''
                // $line===''
                str_starts_with($line, '<source ') ||
                str_starts_with($line, '<div style="display: table;') ||
                str_starts_with($line, '<iframe') ||
                str_starts_with($line, '<img src=') ||
                str_starts_with($line, '<p class="legal">') 
                // str_starts_with($line, '') ||
                // str_starts_with($line, '') ||
                // str_starts_with($line, '') ||
                // str_starts_with($line, '') ||
                // str_starts_with($line, '') ||
            ) {
                continue;
            }


        if (str_contains($line, "<")) {
            // FIXME: don't just ignore these lines
            // used for poetry
            // pard($line);
            continue;
        }

        foreach(explode(' ', $line) as $word) {
            
            if (
                empty($word) || !preg_match("/[a-z]/i", $word)
            ) {
                // If it's empty or has no letters, skip.
                continue;
            }

            $word = strtolower($word);

            echo ("({$word})");
        }

    }
    sleep(1);
}