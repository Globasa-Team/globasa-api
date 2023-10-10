<?php
use PHPUnit\Framework\TestCase;
use globasa_api\Term_parser;
use globasa_api\App_log;

require_once(__DIR__."/../models/Term_parser.php");
require_once(__DIR__."/../models/App_log.php");

final class EtymologyNatlangTest extends TestCase
{
    var $csv_headers = ["Word","Category","WordClass","OfficialWord","TranslationEng",                   "SearchTermsEng","TranslationEpo","TranslationSpa",     "TranslationDeu",                                        "Synonyms","Antonyms","Example","Tags","LexiliAsel",                                                                             "TranslationFra","TranslationRus","TranslationZho"];
    var $csv_data =    ["a",   "root",    "il",       "TRUE",        "ah (_denotes surprise or wonder_)","",              "ha",            "ah",                 "ah (_Ausdruck der Überraschung oder der Verwunderung_)","",        "",        "",       "",    "Putunhwa (啊 “a”), Englisa (ah), Doycisa (ah), Espanisa (ah), Rusisa (ах “akh”, a “a”)", "ah",            "а",             "啊"];
    var $csv_data_2 =  ["hala",	"root",   "n/f.oj",	  "TRUE",	    "solution; solve",		             "",              "solvo; solvi",  "soluciÃ³n; resolver","",                                                      "",        "",        "",       "",    "Arabisa (Ø­Ù„ â€œhalaâ€), Hindisa (à¤¹à¤² â€œhalâ€), Parsisa (Ø­Ù„ â€œhalâ€), Turkisa (halletmek)", "",  "",              ""];


    public function testClassCreatesInstanceSuccessfully(): void
    {
        $tp = new Term_parser($this->csv_headers, null, null);

        // This is really pretty useless as it should always be true in this case.
        $this->assertInstanceOf(
            Term_parser::class,
            $tp
            );
    }


    public function testParseNatlangEtymology(): void
    {

        $tp = new Term_parser($this->csv_headers, null, null);

        //
        // Example ah!
        //
        [$ignore, $parsed] = $tp->parse($this->csv_data, null, null);
        $expected = [
            "Putunhwa"=>"啊 “a”",
            "Englisa"=>"ah",
            "Doycisa"=>"ah",
            "Espanisa"=>"ah",
            "Rusisa"=>"ах “akh”, a “a”"
        ];
        $this->assertIsArray($parsed['etymology']['natlang']);
        $this->assertEqualsCanonicalizing($expected, $parsed['etymology']['natlang']);
    }


    public function testParseNatlangEtymologyWithNoExample(): void
    {

        //
        // Example: no example
        //
        $tp = new Term_parser($this->csv_headers, null, null);
        $this->csv_data[13] = 'Englisa, Rusisa, Klingon (test)';
        [$ignore, $parsed] = $tp->parse($this->csv_data, null, null);
        $expected = ['Englisa'=>"", 'Rusisa'=>"", 'Klingon'=>"test"];
        $this->assertIsArray($parsed['etymology']['natlang']);
        $this->assertEquals($expected, $parsed['etymology']['natlang']);
        // $this->assertNotEquals($expected, $parsed['etymology natlang']);


    }


    public function testLogsNatlangEtymologyWithEnclosureError(): void
    {
        $log = new App_log();
        $tp = new Term_parser($this->csv_headers, null, $log);

        $this->csv_data[13] = 'Englisa(, Rusisa, Klingon test';
        [$ignore, $parsed] = $tp->parse($this->csv_data, null, $log);
        $expected = ['Englisa'=>"", 'Rusisa'=>"", 'Klingon'=>"test"];
        $this->assertIsArray($parsed['etymology']['natlang']);
        $this->assertEquals(expected: ['Englisa'=>', Rusisa, Klingon test'], actual: $parsed['etymology']['natlang']);
        $this->assertEquals(expected: "Error: Term `a` has malformed etymology, missing `)`", actual: $log->get_last_message());
    }


    public function testParseHttpsLinkEtymology(): void
    {
        $this->csv_data[13] = "https://example.com";
        $tp = new Term_parser($this->csv_headers, null, null);
        [$ignore, $parsed] = $tp->parse($this->csv_data);


        $this->assertEquals(
            "https://example.com",
            $parsed['etymology']['link']
            );
            
    }

    
}
