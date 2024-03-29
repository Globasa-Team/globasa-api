<?php 
use PHPUnit\Framework\TestCase;
use globasa_api\Term_parser;
use globasa_api\App_log;

require_once(__DIR__."/../models/Term_parser.php");
require_once(__DIR__."/../models/App_log.php");

final class EtymologyLinkTest extends TestCase
{
    var $csv_headers = ["Word","Category","WordClass","OfficialWord","TranslationEng",                   "SearchTermsEng","TranslationEpo","TranslationSpa","TranslationDeu",                                        "Synonyms","Antonyms","Example","Tags","LexiliAsel",                                                                             "TranslationFra","TranslationRus","TranslationZho"];
    var $csv_data =    ["a",   "root",    "il",       "TRUE",        "ah (_denotes surprise or wonder_)","",              "ha",            "ah",            "ah (_Ausdruck der Überraschung oder der Verwunderung_)","",        "",        "",       "",    "Putunhwa (啊 “a”), Englisa (ah), Doycisa (ah), Espanisa (ah), Rusisa (ах “akh”, a “a”)", "ah",            "а",             "啊"];

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
