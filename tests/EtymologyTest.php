<?php

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use WorldlangDict\API\Term_parser;
use WorldlangDict\API\App_log;

require_once(__DIR__ . '/TermParserData.php');
require_once(__DIR__ . "/../models/Term_parser.php");
require_once(__DIR__ . "/../models/App_log.php");
require_once(__DIR__ . "/../vendor/parsedown/Parsedown.php");
require_once(__DIR__ . "/../helpers/slugify.php");

final class EtymologyTest extends TestCase
{
    var $cfg;
    var $tp;

    public function setUp(): void
    {
        global $cfg;
        $cfg['instance_name'] = 'PHPUnitTest';
        $cfg['wl_code_short'] = 'wld';
        $cfg['log'] = new App_log($cfg);
        $cfg['parsedown'] = new Parsedown;
        $this->cfg = $cfg;
        $this->tp = new Term_parser(TermParserData::$csv_headers);
    }


    #[DataProviderExternal(TermParserData::class, 'etymologyData')]
    public function testParseEtymology(array $csv_data, array $expected): void
    {
        [$ignore, $parsed] = $this->tp->parse_term($csv_data);
        $this->assertEquals(
            $expected['etymology'],
            $parsed['etymology']
        );
    }


    public function testNatlangEnclosureErrorLogged(): void
    {
        $csv_data = TermParserData::$csv_data;
        $csv_data[TermParserData::ETYMOLOGY] = 'Englisa(, Rusisa, Klingon test';
        [$ignore, $parsed] = $this->tp->parse_term($csv_data);
        $this->assertEquals(
            expected: '- Etymology Error: Term `a` has one of ():;-+,? in language name `Englisa(, Rusisa, Klingon test`. (Possibly caused by missing a comma from previous language?)',
            actual: $this->cfg['log']->get_last_message()
        );
    }
}
