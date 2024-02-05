<?php

function load_csv($file, &$csv_data)
{
    $dictionaryCSV = fopen($file, 'r');
    if ($dictionaryCSV === false) {
        die("Failed to open dictionary CSV");
    }
    //TODO: What does this do on failure? Empty file? No file found?
    $columnNames = fgetcsv($dictionaryCSV);

    pard_counter_start("Load old CSV data");
    while (($word = fgetcsv($dictionaryCSV)) !== false) {
        $newWord = null;
        foreach ($word as $key=>$datum) {
            $newWord[empty($columnNames[$key])?'Word':$columnNames[$key]] = $datum;
        }
        $wordIndex = slugify($word[0]);
        if (empty($wordIndex)) continue; // Skip blank lines
        $csv_data[$wordIndex] = $newWord;
        usleep(SMALL_IO_DELAY);
        pard_counter_next();
    }
    pard_counter_end();
}