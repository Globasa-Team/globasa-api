<?php

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
        $wordIndex = slugify($word[0]);
        if (empty($wordIndex)) continue; // Skip blank lines
        $dictionary[$wordIndex] = $newWord;
        usleep(SMALL_IO_DELAY);
    }
    return $dictionary;
}