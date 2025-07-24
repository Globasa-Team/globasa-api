<?php

function load_csv($file, &$csv_data)
{
    global $cfg;

    $dictionaryCSV = fopen($file, 'r');
    if ($dictionaryCSV === false) {
        die("Failed to open dictionary CSV");
    }
    //TODO: What does this do on failure? Empty file? No file found?
    $columnNames = fgetcsv($dictionaryCSV, escape:"");

    \pard\counter_start("Load old CSV data");
    while (($word = fgetcsv($dictionaryCSV, escape:"")) !== false) {
        $newWord = null;
        foreach ($word as $key=>$datum) {
            if (!isset(COLUMN_MAP[$columnNames[$key]])) {
                // Skip things that aren't being processed yet
                continue;
            }
            $newWord[empty($columnNames[$key])?'Word':COLUMN_MAP[$columnNames[$key]]] = $datum;
        }
        $wordIndex = slugify($word[0]);
        if (empty($wordIndex)) continue; // Skip blank lines
        if (!empty($newWord['slug_mod'])) {
            $wordIndex .= '_'.slugify($newWord['slug_mod']);
        }
        if (!empty($csv_data[$wordIndex])) {
            $cfg['log']->add("Error: word index already exists: ".$wordIndex);
        }

        $csv_data[$wordIndex] = $newWord;
        usleep(SMALL_IO_DELAY);
        \pard\counter_next();
    }
    \pard\counter_end();
}