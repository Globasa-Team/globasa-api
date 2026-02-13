<?php

declare(strict_types=1);

namespace WorldlangDict\API;

class Entry
{
    private static $possible_onsets = [
        'bl', 'fl', 'gl', 'kl', 'pl', 'vl',
        'br', 'dr', 'fr', 'gr', 'kr', 'pr', 'tr', 'vr',
        'bw', 'cw', 'dw', 'fw', 'gw', 'hw', 'jw', 'kw', 'lw', 'mw', 'nw', 'pw', 'rw', 'sw', 'tw', 'vw', 'xw', 'zw',
        'by', 'cy', 'dy', 'fy', 'gy', 'hy', 'jy', 'ky', 'ly', 'my', 'ny', 'py', 'ry', 'sy', 'ty', 'vy', 'xy', 'zy'
    ];

    const VOWELS = ['a','e','o','u','i'];
    const HYPH_POINT = '&#x2027;'; // Alternative: &#xB7; / &centerdot;

    private static function all_consonants(string $input):bool {
        foreach(grapheme_str_split($input) as $char) {
            if (in_array($char, self::VOWELS)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the final part of derived etymology, or the term, or the slug.
     */
    public static function get_final_morpheme(string $entry_slug): string
    {
        global $dict;
        $morpheme = "";

        if (isset($dict[$entry_slug]['etymology']['derived'])) {
            $morpheme = $dict[$entry_slug]['etymology']['derived'][array_key_last($dict[$entry_slug]['etymology']['derived'])];
        } elseif (isset($dict[$entry_slug]['term'])) {
            $morpheme = mb_strtolower($dict[$entry_slug]['term']);
        } else {
            $morpheme = $entry_slug;
            trigger_error("Tried to get final morpheme, but entry doesn't exist in dictionary word list.");
            \pard\m("Tried to get final morpheme, but entry doesn't exist in dictionary word list.", 'Error', true);
        }
        return $morpheme;
    }

    public static function get_syllables(string $term): array
    {
        $syllables = [];

        # divide into parts by vowels
        $current_syllable = '';
        foreach(grapheme_str_split($term) as $char) {
            $current_syllable .= $char;
            if (in_array($char, self::VOWELS)){
                $syllables[] = $current_syllable;
                $current_syllable = '';
            }
        }
        
        // Add current syllable if not empty
        if ($current_syllable) {
            $syllables[] = $current_syllable;
        }
        
        // append last coda if any
        if (self::all_consonants(array_last($syllables))) {
            $coda = array_pop($syllables);
            $syllables[array_key_last($syllables)] .= $coda;
        }
        // Occasionally the array loses it's numbering and needs to be re-indexed
        $syllables = array_values($syllables);
        
        # break CCC into C-CC
        for ($i=1; $i < count($syllables); $i++) {
            if (mb_strlen($syllables[$i]) > 3 and self::all_consonants(mb_substr($syllables[$i], 0, 3))) { // first 3
                $syllables[$i-1] .= $syllables[$i][0]; // copy first letter
                $syllables[$i] = substr($syllables[$i], 1); // remove first letter
            }
        }
        
        # break CCV into C-CV if CC is not allowed onset
        for ($i=1; $i < count($syllables); $i++) {
            if (mb_strlen($syllables[$i]) > 2 && self::all_consonants(substr($syllables[$i], 0, 2)) && !in_array(substr($syllables[$i], 0, 2), self::$possible_onsets)) {
                $syllables[$i-1] .= $syllables[$i][0]; // copy first letter
                $syllables[$i] = substr($syllables[$i], 1); // remove first letter
            }
        }
        
        return $syllables;
    }
}
