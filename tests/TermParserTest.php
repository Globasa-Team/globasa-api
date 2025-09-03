<?php

use PHPUnit\Framework\TestCase;
use globasa_api\Term_parser;

require_once(__DIR__ . "/../models/Term_parser.php");

final class TermParserTest extends TestCase
{
    var $csv_headers = ["Word", "Category", "WordClass", "OfficialWord", "TranslationEng",
                    "SearchTermsEng", "TranslationEpo", "TranslationSpa", "TranslationDeu",
                    "Synonyms", "Antonyms", "Example", "Tags", "LexiliAsel",
                    "TranslationFra", "TranslationRus", "TranslationZho"];

    public function testClassCreatesInstanceSuccessfully(): void
    {
        $tp = new Term_parser($this->csv_headers, null, null);

        // This is really pretty useless as it should always be true in this case.
        $this->assertInstanceOf(
            Term_parser::class,
            $tp
        );
    }
}
