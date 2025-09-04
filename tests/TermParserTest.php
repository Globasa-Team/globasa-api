<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WorldlangDict\API\Term_parser;
use WorldlangDict\API\App_log;

require_once(__DIR__ . "/../models/App_log.php");
require_once(__DIR__ . "/../models/Term_parser.php");
require_once(__DIR__ . "/../vendor/parsedown/Parsedown.php");

final class TermParserTest extends TestCase
{
    var $csv_headers = [
        "Word",
        "Category",
        "WordClass",
        "OfficialWord",
        "TranslationEng",
        "SearchTermsEng",
        "TranslationEpo",
        "TranslationSpa",
        "TranslationDeu",
        "Synonyms",
        "Antonyms",
        "Example",
        "Tags",
        "LexiliAsel",
        "TranslationFra",
        "TranslationRus",
        "TranslationZho"
    ];

    public function testClassCreatesInstanceSuccessfully(): void
    {
        global $cfg;
        $cfg['instance_name'] = 'PHPUnitTest';
        $cfg['wl_code_short'] = 'wld';
        $cfg['log'] = new App_log($cfg);
        $cfg['parsedown'] = new Parsedown();
        $tp = new Term_parser($this->csv_headers);

        // This is really pretty useless as it should always be true in this case.
        $this->assertInstanceOf(
            Term_parser::class,
            $tp
        );
    }
}
