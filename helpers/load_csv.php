<?php

const SMALL_IO_DELAY = 50000; // 50k microseconds = a twentieth of a second

function load_csv($file)
{
    $dictionaryCSV = fopen($file, 'r');
    if ($dictionaryCSV === false) {
        die("Failed to open dictionary CSV");
    }
    //What does this do on failure? Empty file? No file found?
    $columnNames = fgetcsv($dictionaryCSV);

    while (($word = fgetcsv($dictionaryCSV)) !== false) {
        $newWord = null;
        foreach ($word as $key=>$datum) {
            $newWord[empty($columnNames[$key])?'Word':$columnNames[$key]] = $datum;
        }
        $wordIndex = strtolower(trim($word[0]));
        if (empty($wordIndex)) continue; // Skip blank lines
        $dictionary[$wordIndex] = $newWord;
        // usleep(SMALL_IO_DELAY);
    }
    return $dictionary;
}