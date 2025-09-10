<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WorldlangDict\API\Term_parser;
use WorldlangDict\API\App_log;

require_once(__DIR__ . '/TermParserData.php');
require_once(__DIR__ . '/../models/App_log.php');
require_once(__DIR__ . '/../models/Term_parser.php');
require_once(__DIR__ . '/../vendor/parsedown/Parsedown.php');
require_once(__DIR__ . "/../helpers/slugify.php");

final class TermParserTest extends TestCase
{
    private $tp;

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

    public function setUp(): void
    {
        global $cfg;
        $cfg = array();
        $cfg['instance_name'] = 'PHPUnitTest';
        $cfg['wl_code_short'] = 'wld';
        $cfg['log'] = new App_log($cfg);
        $cfg['parsedown'] = new Parsedown();
        $this->tp = new Term_parser($this->csv_headers);
    }



    public function testClassCreatesInstanceSuccessfully(): void
    {

        // This is really pretty useless as it should always be true in this case.
        $this->assertInstanceOf(
            Term_parser::class,
            $this->tp
        );
    }

    public function testParseCsvRow(): void
    {
        [$ignore, $parsed] = $this->tp->parse_term(TermParserData::$csv_data);
        $this->assertEquals(
            TermParserData::$expected_result,
            $parsed
        );
    }
}
