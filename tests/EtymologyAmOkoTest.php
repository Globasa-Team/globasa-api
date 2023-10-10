<?php 
use PHPUnit\Framework\TestCase;
use globasa_api\Term_parser;
use globasa_api\App_log;

require_once(__DIR__."/../models/Term_parser.php");
require_once(__DIR__."/../models/App_log.php");

final class EtymologyAmOkoTest extends TestCase
{
    var $tp;
    const ETYMOLOGY = 13; // Etymology Index
    var $csv_headers = ["Word","Category","WordClass","OfficialWord","TranslationEng","SearchTermsEng","TranslationEpo","TranslationSpa","TranslationDeu","Synonyms","Antonyms","Example","Tags","LexiliAsel"               ,   "TranslationFra","TranslationRus","TranslationZho"];
    var $csv_data =    ["Word","Category","WordClass","OfficialWord","TranslationEng","SearchTermsEng","TranslationEpo","TranslationSpa","TranslationDeu","Synonyms","Antonyms","Example","Tags","LexiliAsel"               ,   "TranslationFra","TranslationRus","TranslationZho"];
    var $csv_data1 = [
                  0 => ["Word","Category","WordClass","OfficialWord","TranslationEng","SearchTermsEng","TranslationEpo","TranslationSpa","TranslationDeu","Synonyms","Antonyms","Example","Tags","Am oko _1-1_"             ,   "TranslationFra","TranslationRus","TranslationZho"],
                  1 => ["Word","Category","WordClass","OfficialWord","TranslationEng","SearchTermsEng","TranslationEpo","TranslationSpa","TranslationDeu","Synonyms","Antonyms","Example","Tags","Am oko _2-1_, _2-2_, _2-3_",  "TranslationFra","TranslationRus","TranslationZho"],
                  2 => ["Word","Category","WordClass","OfficialWord","TranslationEng","SearchTermsEng","TranslationEpo","TranslationSpa","TranslationDeu","Synonyms","Antonyms","Example","Tags","Am oko _3-1_, _3-2_ ji _3-3_", "TranslationFra","TranslationRus","TranslationZho"]
    ];

    
    protected function setUp(): void
    {
        $this->tp = new Term_parser($this->csv_headers, null, null);
    }

    public function testAmOko(): void
    {
        // Single item
        $this->csv_data[self::ETYMOLOGY] = "Am oko _1-1_"; "Am oko _2-1_, _2-2_, _2-3_"; "Am oko _3-1_, _3-2_ ji _3-3_";
        [$raw, $parsed] = $this->tp->parse($this->csv_data);
        $this->assertEqualsCanonicalizing(
            ['1-1'],
            $parsed['etymology']['am oko']
            );
            
        // Tripple comma
        $this->csv_data[self::ETYMOLOGY] = "Am oko _2-1_, _2-2_, _2-3_";
        [$raw, $parsed] = $this->tp->parse($this->csv_data);
        $this->assertEqualsCanonicalizing(
            ['2-1', '2-2', '2-3'],
            $parsed['etymology']['am oko']
            );

        // Comma and ji
        $this->csv_data[self::ETYMOLOGY] = "Am oko _3-1_, _3-2_ ji _3-3_";
        [$raw, $parsed] = $this->tp->parse($this->csv_data);
        $this->assertEqualsCanonicalizing(
            ['3-1', '3-2', '3-3'],
            $parsed['etymology']['am oko']
            );

    }
    
    public function testAmOkoOnTermJi(): void
    {
        $this->csv_data[self::ETYMOLOGY] = "Am oko _ji_";
        [$raw, $parsed] = $this->tp->parse($this->csv_data);
        
        $this->assertEquals(
            [1=>"ji"],
            $parsed['etymology']['am oko']
            );
    }
    
}
