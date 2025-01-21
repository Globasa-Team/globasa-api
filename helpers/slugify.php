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
 */
function slugify(string $text) {

    $rules = <<<'RULES'
        :: Any-Latin;
        :: NFD;
        :: [:Nonspacing Mark:] Remove;
        :: NFC;
        :: [^-[:^Punctuation:]] Remove;
        :: Lower();
    RULES;
    
    return str_replace(' ', '_', 
                \Transliterator::createFromRules($rules)
                    ->transliterate(
                        strtolower(trim($text))
                        )
            );
    
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
function slugify_strict(string $text) {

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
        ->transliterate( $text );
    
}