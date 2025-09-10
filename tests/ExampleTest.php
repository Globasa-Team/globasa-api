<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . "/../src/example.php");
require_once(__DIR__ . "/../vendor/parsedown/Parsedown.php");

final class ExampleTest extends TestCase
{
    var $p = 1;
    var $c = ["PHPUnit Test"];

    public static function sentenceProvider(): array
    {
        return [
            [
                "Kete le fale to?",
                [
                    'kete' => [1 => [0 => ['text' => 'Kete le fale to?', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'le' => [1 => [0 => ['text' => 'Kete le fale to?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'fale' => [1 => [0 => ['text' => 'Kete le fale to?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'to' => [1 => [0 => ['text' => 'Kete le fale to?', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            [
                "Bante le cudu to.",
                [
                    'bante' => [1 => [0 => ['text' => 'Bante le cudu to.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'le' => [1 => [0 => ['text' => 'Bante le cudu to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'cudu' => [1 => [0 => ['text' => 'Bante le cudu to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'to' => [1 => [0 => ['text' => 'Bante le cudu to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            [
                "Mi no jixi ku kete.",
                [
                    'mi' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'no' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'jixi' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'ku' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'kete' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            [
                "Keto le okur?",
                [
                    'keto' => [1 => [0 => ['text' => 'Keto le okur?', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'le' => [1 => [0 => ['text' => 'Keto le okur?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'okur' => [1 => [0 => ['text' => 'Keto le okur?', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            [
                "Mi le fale ban bur to.",
                [
                    'mi' => [1 => [0 => ['text' => 'Mi le fale ban bur to.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'le' => [1 => [0 => ['text' => 'Mi le fale ban bur to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'fale' => [1 => [0 => ['text' => 'Mi le fale ban bur to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'ban' => [1 => [0 => ['text' => 'Mi le fale ban bur to.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'bur' => [1 => [0 => ['text' => 'Mi le fale ban bur to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'to' => [1 => [0 => ['text' => 'Mi le fale ban bur to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            [
                "Hinto sen keto?",
                [
                    'hinto' => [1 => [0 => ['text' => 'Hinto sen keto?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'sen' => [1 => [0 => ['text' => 'Hinto sen keto?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'keto' => [1 => [0 => ['text' => 'Hinto sen keto?', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            [
                "Hin xey sen lil.",
                [
                    'hin' => [1 => [0 => ['text' => 'Hin xey sen lil.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'xey' => [1 => [0 => ['text' => 'Hin xey sen lil.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'sen' => [1 => [0 => ['text' => 'Hin xey sen lil.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'lil' => [1 => [0 => ['text' => 'Hin xey sen lil.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            [
                "Ete sen bon insan.",
                [
                    'ete' => [1 => [0 => ['text' => 'Ete sen bon insan.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'sen' => [1 => [0 => ['text' => 'Ete sen bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'bon' => [1 => [0 => ['text' => 'Ete sen bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'insan' => [1 => [0 => ['text' => 'Ete sen bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            [
                "Multi insan no jixi hinto.",
                [
                    'multi' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'insan' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'no' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'jixi' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'hinto' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            [
                "Fe duli mara, bur xey okur tas bon insan.",
                [
                    'fe' => [1 => [0 => ['text' => 'Fe duli mara, bur xey okur tas bon insan.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'duli' => [1 => [0 => ['text' => 'Fe duli mara, bur xey okur tas bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'mara' => [1 => [0 => ['text' => 'Fe duli mara, bur xey okur tas bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'bur' => [1 => [0 => ['text' => 'Fe duli mara, bur xey okur tas bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'xey' => [1 => [0 => ['text' => 'Fe duli mara, bur xey okur tas bon insan.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'okur' => [1 => [0 => ['text' => 'Fe duli mara, bur xey okur tas bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'tas' => [1 => [0 => ['text' => 'Fe duli mara, bur xey okur tas bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'bon' => [1 => [0 => ['text' => 'Fe duli mara, bur xey okur tas bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'insan' => [1 => [0 => ['text' => 'Fe duli mara, bur xey okur tas bon insan.', 'cite' => [0 => 'PHPUnit Test'],],],],
                ],
            ],
            // [
            // "___sentence___",[
            //     '____'=>[1=>[0=>['text'=>'___sentence___','cite'=>[0=>'PHPUnit Test'],],],],
            //     '____'=>[1=>[0=>['text'=>'___sentence___','cite'=>[0=>'PHPUnit Test',],],],],
            //     '____'=>[1=>[0=>['text'=>'___sentence___','cite'=>[0=>'PHPUnit Test',],],],],
            //     '____'=>[1=>[0=>['text'=>'___sentence___','cite'=>[0=>'PHPUnit Test',],],],],
            // ],

        ];
    }

    protected function setUp(): void
    {
        global $examples, $wld_index, $pd;

        $pd = new Parsedown();
        $examples = array();
        $wld_index = ["ban" => null, "bante" => null, "bon" => null, "bur" => null, "cudu" => null, "day" => null, "de" => null, "denpul" => null, "dua" => null, "duli" => null, "ete" => null, "fale" => null, "fe" => null, "femixu" => null, "hare" => null, "hin" => null, "hinto" => null, "insan" => null, "inya" => null, "jismu" => null, "jixi" => null, "kete" => null, "keto" => null, "kom" => null, "ku" => null, "le" => null, "lil" => null, "manixu" => null, "mara" => null, "mi" => null, "mi" => null, "mida" => null, "mon" => null, "multi" => null, "no" => null, "okur" => null, "sen" => null, "su" => null, "tas" => null, "to" => null, "xey" => null,];
    }

    public function testParsingParagraph(): void
    {
        global $examples;

        // $p = "Kete: le fale to? Bante; le cudu to. Mi no jixi ku kete! Keto le okur? Mi le fale ban bur to. Hinto sen keto? Hin xey sen lil. Ete sen bon insan. Multi insan no jixi hinto.” Fe duli mara, bur xey okur tas bon insan.";
        // $p = "mi: mi mi mi? mi; mi mi mi. mi mi mi mi mi mi. mi mi mi?. mi mi mi mi mi mi. \u{2018}mi mi mi?\u{2019} \u{201C}mi mi mi mi.\u{201D} mi mi mi mi.’ mi mi mi mi mi.” mi mi mi, mi mi mi mi mi mi.";
        // $p = "Kete: le fale to? 'Bante; le cudu to.' \"Mi no jixi ku kete!\" Keto le okur? Mi le fale ban bur to.  \u{2018}Hinto sen keto?\u{2019} \u{201C}Hin xey sen lil.\u{201D} ‘Ete sen bon insan.’ “Multi insan no jixi hinto.” Fe duli mara, bur xey okur tas bon insan.";

        $p = "mi: mi mi mi? 'mi; mi mi mi.' \"mi mi mi mi mi!\" mi mi mi? mi mi mi mi mi mi.  \u{2018}mi mi mi?\u{2019} \u{201C}mi mi mi mi.\u{201D} ‘mi mi mi mi.’ “mi mi mi mi mi.” mi mi mi, mi mi mi mi mi mi.";
        $expected = ['mi' => [1 => [
            0 => ['text' => 'mi: mi mi mi?', 'cite' => [0 => 'PHPUnit Test',]],
            1 => ['text' => "'mi; mi mi mi.'", 'cite' => [0 => 'PHPUnit Test']],
            2 => ['text' => '"mi mi mi mi mi!"', 'cite' => [0 => 'PHPUnit Test']],
            3 => ['text' => 'mi mi mi?', 'cite' => [0 => 'PHPUnit Test',]],
            4 => ['text' => "mi mi mi mi mi mi.", 'cite' => [0 => 'PHPUnit Test',]],
            5 => ['text' => "\u{2018}mi mi mi?\u{2019}", 'cite' => [0 => 'PHPUnit Test',]],
            6 => ['text' => "\u{201C}mi mi mi mi.\u{201D}", 'cite' => [0 => 'PHPUnit Test',]],
            7 => ['text' => "‘mi mi mi mi.’", 'cite' => [0 => 'PHPUnit Test',]],
            8 => ['text' => "“mi mi mi mi mi.”", 'cite' => [0 => 'PHPUnit Test',]],
            9 => ['text' => "mi mi mi, mi mi mi mi mi mi.", 'cite' => [0 => 'PHPUnit Test',]],
        ]]];

        \WorldlangDict\Examples\parse_paragraph($p, $this->c, $this->p);
        $this->assertSame($expected, $examples);
    }

    #[DataProvider('sentenceProvider')]
    public function testParsingSentence(string $sentence, array $expected): void
    {
        global $examples;
        \WorldlangDict\Examples\parse_sentence($sentence, $this->c, $this->p);
        $this->assertSame($expected, $examples);
    }
}
