<?php

declare(strict_types=1);

namespace WorldlangDict\API;

class Word
{

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
}
