<?php

use PHPUnit\Framework\TestCase;
use WorldlangDict\API\Term_parser;
use WorldlangDict\API\App_log;

require_once(__DIR__ . "/../../models/Term_parser.php");
require_once(__DIR__ . "/../../models/App_log.php");
require_once(__DIR__ . "/../../vendor/parsedown/Parsedown.php");

final class EtymologyNatlangTest extends TestCase
{
    var $csv_headers = ["Word", "Category", "WordClass", "OfficialWord", "TranslationEng", "SearchTermsEng", "TranslationEpo", "TranslationSpa", "TranslationDeu", "Synonyms", "Antonyms", "Example", "Tags", "LexiliAsel",                                                                             "TranslationFra", "TranslationRus", "TranslationZho"];
    var $csv_data = ["a", "root", "il", "TRUE", "ah (_denotes surprise or wonder_)", "", "ha", "ah", "ah (_Ausdruck der Überraschung oder der Verwunderung_)", "", "", "", "", "Putunhwa (啊 “a”), Englisa (ah), Doycisa (ah), Espanisa (ah), Rusisa (ах “akh”, a “a”)", "ah", "а", "啊"];
    var $csv_data_2 =  ["hala", "root", "n/f.oj", "TRUE", "solution; solve", "", "solvo; solvi",  "soluciÃ³n; resolver", "", "", "", "", "", "Arabisa (Ø­Ù„ â€œhalaâ€), Hindisa (à¤¹à¤² â€œhalâ€), Parsisa (Ø­Ù„ â€œhalâ€), Turkisa (halletmek)", "",  "", ""];
    var $tp = null;
    var $cfg = null;

    public function setUp(): void
    {
        global $cfg;
        $cfg['report_level'] = 0;
        $cfg['instance_name'] = 'PHPUnitTest';
        $cfg['log'] = new App_log($cfg);
        $cfg['parsedown'] = new Parsedown;
        $this->cfg = $cfg;
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


    public function testParseNatlangEtymology(): void
    {

        $tp = new Term_parser($this->csv_headers);

        //
        // Example ah!
        //
        [$ignore, $parsed] = $tp->parse_term($this->csv_data);
        $expected = [
            "Putunhwa" => "啊 &ldquo;a&rdquo;",
            "Englisa" => "ah",
            "Doycisa" => "ah",
            "Espanisa" => "ah",
            "Rusisa" => "ах &ldquo;akh&rdquo;, a &ldquo;a&rdquo;"
        ];
        $this->assertIsArray($parsed['etymology']['natlang']);
        $this->assertEqualsCanonicalizing($expected, $parsed['etymology']['natlang']);
    }


    public function testParseNatlangEtymologyWithNoExample(): void
    {

        //
        // Example: no example
        //
        $tp = new Term_parser($this->csv_headers);
        $this->csv_data[13] = 'Englisa, Rusisa, Klingon (test)';
        [$ignore, $parsed] = $tp->parse_term($this->csv_data, null, null);
        $expected = ['Englisa' => "", 'Rusisa' => "", 'Klingon' => "test"];
        $this->assertIsArray($parsed['etymology']['natlang']);
        $this->assertEquals($expected, $parsed['etymology']['natlang']);
        // $this->assertNotEquals($expected, $parsed['etymology natlang']);


    }


    public function testLogsNatlangEtymologyWithEnclosureError(): void
    {
        $tp = new Term_parser($this->csv_headers);

        $this->csv_data[13] = 'Englisa(, Rusisa, Klingon test';
        [$ignore, $parsed] = $tp->parse_term($this->csv_data);
        $expected = ['Englisa' => "", 'Rusisa' => "", 'Klingon' => "test"];
        $this->assertIsArray($parsed['etymology']['natlang']);
        $this->assertEquals(expected: ['Englisa(, Rusisa, Klingon test' => ''], actual: $parsed['etymology']['natlang']);
        $this->assertEquals(
            expected: "- Etymology Error: Term `a` has one of ():;-+,? in language name `Englisa(, Rusisa, Klingon test`. (Possibly caused by missing a comma from previous language?)",
            actual: $this->cfg['log']->get_last_message()
        );
    }


    public function testParseHttpsLinkEtymology(): void
    {
        $this->csv_data[13] = "https://example.com";
        $tp = new Term_parser($this->csv_headers);
        [$ignore, $parsed] = $tp->parse_term($this->csv_data);


        $this->assertEquals(
            "https://example.com",
            $parsed['etymology']['link']
        );
    }
}
