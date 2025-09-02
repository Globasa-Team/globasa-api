<?php

/**
 * Takes string and converts it to lower case ASCII, trim spaces,
 * removing punctuation (except hyphens), stripping off accents.
 * 
 * Leaves spaces intact.
 * 
 * For URLs accents are now acceptable in URLs.
 * Spaces work sometimes but it's probably best to hypenize them
 * 
 * https://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string/13331948#13331948
 * https://unicode-org.github.io/icu/userguide/transforms/general/
 * See function below's reference, also
 */
function slugify(string $text)
{
    /**
     * Heredoc string literal of the rules
     *
     * :: NFD;
     * :: [:Nonspacing Mark:] Remove;
     * :: NFC;
     *      `NFD`/`NFC` markers for Normalization Form something-or-other.
     *      `[:Nonspacing Mark:] Remove;` remove diacritics/accents.
     * :: Lower();
     *      makes string lower case
     * :: Any-Latin;
     *      where possible, converts unicode to asci representation,
     *      eg « → ‘«’, © → ‘(C)’, Æ → AE
     * :: [^-\_[:^Punctuation:]] Remove;
     *      Remove all punctuation except hyphens and underscores.
     *      Literally remove everything that is not a hyphen, underscore or not punctuation.
     *      Note double negative (not not puncutation).
     * [:White_Space:] > '_';
     *      Replace all white space with underscores
     */
    $rules = <<<RULES
        :: NFD;
        :: [:Nonspacing Mark:] Remove;
        :: NFC;
        :: Lower();
        :: Any-Latin;
        :: [^-\_[:^Punctuation:]] Remove;
        [:White_Space:] > '_';
        RULES;

    return \Transliterator::createFromRules($rules)->transliterate($text);
}


/**
 * Takes string and converst it to lower case ASCII, trim spaces,
 * removing punctuation (except hyphens), stripping off accents,
 * changing spaces to hypens.
 * 
 * For URLs accents are now acceptable in URLs.
 * Spaces work sometimes but it's probably best to hypenize them
 * 
 * https://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string/13331948#13331948
 */
function slugify_strict(string $text)
{
    $rules = <<<'RULES'
        :: Any-Latin;
        :: NFD;
        :: [:Nonspacing Mark:] Remove;
        :: NFC;
        :: [^-[:^Punctuation:]] Remove;
        :: Lower();
        [-[:Separator:]]+ > '-';
    RULES;

    return \Transliterator::createFromRules($rules)
        ->transliterate($text);
}
