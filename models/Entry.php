<?php
/**
 * Entry model
 * 
 * Functions to generate entry data based on the parsed
 * entry input.
 * 
 *  Not Tested:
 *   get_final_morpheme
 */


declare(strict_types=1);

namespace WorldlangDict\API;

class Entry
{
    /** @var array Array of possible syllable onsets. */
    private static $possible_onsets = [
        'bl', 'fl', 'gl', 'kl', 'pl', 'vl',
        'br', 'dr', 'fr', 'gr', 'kr', 'pr', 'tr', 'vr',
        'bw', 'cw', 'dw', 'fw', 'gw', 'hw', 'jw', 'kw', 'lw', 'mw', 'nw', 'pw', 'rw', 'sw', 'tw', 'vw', 'xw', 'zw',
        'by', 'cy', 'dy', 'fy', 'gy', 'hy', 'jy', 'ky', 'ly', 'my', 'ny', 'py', 'ry', 'sy', 'ty', 'vy', 'xy', 'zy'
    ];

    /** @var array List of vowels */
    const VOWELS = ['a','e','o','u','i'];
    const HYPH_POINT = '&#x2027;'; // Alternative: &#xB7; / &centerdot;


    /**
     * Determine if all characters are consonants.
     * 
     * @param string $text string to check for vowels.
     * 
     * @return bool if the string is all consonants.
     */
    public static function all_consonants(string $text):bool
    {
        foreach(grapheme_str_split($text) as $char) {
            if (in_array($char, self::VOWELS)) {
                return false;
            }
        }
        return true;
    }


    /**
     * Determine if term ends with a vowel or not.
     * 
     * @param array $entry entry to check.
     * 
     * @return bool if entry term ends with a vowel.
     */
    public static function ends_with_vowel(array $entry):bool
    {
        $final_letter = mb_substr($entry['term'], -1);
        return in_array($final_letter, self::VOWELS);
    }


    /**
     * Get the final syllable's coda.
     * 
     * @param $entry
     * @return string final syllable's coda
     */
    public static function get_final_coda(array $entry): string
    {
        preg_match(FINAL_VOWEL_REGEX, array_last($entry['syllables']), $match);
        $pos = mb_strrpos(array_last($entry['syllables']), $match[0]);
        return mb_substr(array_last($entry['syllables']), $pos+1);
    }


    /**
     * Get the final morpheme of an entry term.
     * 
     * @param string $entry_slug to parse.
     * 
     * @return the final part of derived etymology
     * @return string the term, if not derived
     * @return string slug, otherwise
     */
    // TODO: Unit test
    public static function get_final_morpheme(string $entry_slug): string
    {
        global $dict;

        if (isset($dict[$entry_slug]['etymology']['derived'])) {
            return $dict[$entry_slug]['etymology']['derived'][
                array_key_last($dict[$entry_slug]['etymology']['derived'])
            ];
        } elseif (isset($dict[$entry_slug]['term'])) {
            return mb_strtolower($dict[$entry_slug]['term']);
        } else {
            trigger_error("Tried to get final morpheme, but the entry does not have a term field or doesn't exist in dictionary word list.");
            \pard\m("Tried to get final morpheme, but the entry does not have a term field or doesn't exist in dictionary word list.", 'Error', true);
            return $entry_slug;
        }
    }


    /**
     * Get the final syllable.
     * 
     * @param array $entry
     * 
     * @return string the final syllable.
     */
    public static function get_final_syllable(array $entry): string
    {
        return array_last($entry['syllables']);
    }
    

    /**
     * Get the final syllable's vowel.
     * 
     * @param array $entry
     * 
     * @return string vowel
     */
    public static function get_final_vowel(array $entry): string
    {
        preg_match(FINAL_VOWEL_REGEX, array_last($entry['syllables']), $match);
        return array_last($match);
    }


    /**
     * Get the second last syllable's coda.
     * 
     * @param $entry
     * 
     * @return string coda
     */
    public static function get_penult_coda(array $entry): string
    {
        if (count($entry['syllables']) <= 1) return '';

        $penult = array_slice($entry['syllables'], -2, 1);    
        preg_match(FINAL_VOWEL_REGEX, $penult[0], $match);
        $pos = mb_strrpos($penult[0], $match[0]);

        return mb_substr($penult[0], $pos+1);
    }


    /**
     * Get the second last syllable's vowel.
     * 
     * @param $entry
     * 
     * @return string vowel
     */
    public static function get_penult_vowel(array $entry): string
    {
        if (count($entry['syllables']) <= 1) return '';

        $penult = array_slice($entry['syllables'], -2, 1);
        preg_match(FINAL_VOWEL_REGEX, $penult[0], $match);

        return array_first($match);
    }


    /**
     * Get array of syllables
     * 
     * @param $entry
     * 
     * @return array strings of syllables
     */
    public static function get_syllables(array $entry): array
    {
        $syllables = [];

        // divide into parts by vowels
        $current_syllable = '';
        foreach(grapheme_str_split($entry['term']) as $char) {
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
        
        // break CCC into C-CC
        for ($i=1; $i < count($syllables); $i++) {
            if (
                mb_strlen($syllables[$i]) > 3 &&
                self::all_consonants(mb_substr($syllables[$i], 0, 3))
            ) {
                $syllables[$i-1] .= $syllables[$i][0]; // copy first letter
                $syllables[$i] = substr($syllables[$i], 1); // remove first letter
            }
        }
        
        // break CCV into C-CV if CC is not allowed onset
        for ($i=1; $i < count($syllables); $i++) {
            if (
                mb_strlen($syllables[$i]) > 2 &&
                self::all_consonants(substr($syllables[$i], 0, 2)) &&
                !in_array(substr($syllables[$i], 0, 2), self::$possible_onsets)
            ) {
                $syllables[$i-1] .= $syllables[$i][0]; // copy first letter
                $syllables[$i] = substr($syllables[$i], 1); // remove first letter
            }
        }
        
        return $syllables;
    }
    
}
