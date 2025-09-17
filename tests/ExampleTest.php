<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . "/../src/example.php");
require_once(__DIR__ . "/../vendor/parsedown/Parsedown.php");

final class ExampleTest extends TestCase
{
    var $p = 1;
    var $c = ["PHPUnit Test"];

 
    protected function setUp(): void
    {
        global $examples, $wld_index, $pd;
        \pard\status(false); //DEBUG

        $pd = new Parsedown();
        $examples = array();
        $wld_index = ["a" => null, "ban" => null, "bante" => null, "bon" => null, "bur" => null, "cudu" => null, "day" => null, "de" => null, "denpul" => null, "dua" => null, "duli" => null, "ete" => null, "fale" => null, "fe" => null, "femixu" => null, "hare" => null, "hin" => null, "hinto" => null, "insan" => null, "inya" => null, "jismu" => null, "jixi" => null, "kete" => null, "keto" => null, "kom" => null, "ku" => null, "le" => null, "lil" => null, "manixu" => null, "mara" => null, "mi" => null, "mi" => null, "mida" => null, "mon" => null, "multi" => null, "no" => null, "okur" => null, "sen" => null, "su" => null, "tas" => null, "to" => null, "xey" => null,];
    }
    
    #[DataProviderExternal(ExampleData::class, 'sentences')]
    public function testParsingSentence(string $sentence, array $expected): void
    {
        global $examples;
        \WorldlangDict\Examples\parse_sentence($sentence, $this->c, $this->p);
        $this->assertSame($expected, $examples);
    }

    #[DataProviderExternal(ExampleData::class, 'paragraphs')]
    public function testParsingParagraph($input, $expected): void
    {
        global $examples;
        \WorldlangDict\Examples\parse_paragraph($input, $this->c, $this->p);

        // var_dump($expected);
        // var_dump($examples);
        $this->assertSame($expected, $examples);
    }
}



final class ExampleData {
    public static function paragraphs(): array
    {
        return [
            'mi'=> [
                'input'=>"mi: mi mi mi? 'mi; mi mi mi.' \"mi mi mi mi mi!\" mi mi mi? mi mi mi mi mi mi.  \u{2018}mi mi mi?\u{2019} \u{201C}mi mi m’i mi.\u{201D} ‘mi mi m’i mi.’ “mi mi mi mi mi.” mi mi mi, mi mi mi mi mi mi.",
                'expected'=>['mi' => [1 => [
                    0 => ['text' => 'mi: mi mi mi?', 'cite' => [0 => 'PHPUnit Test',]],
                    1 => ['text' => "'mi; mi mi mi.'", 'cite' => [0 => 'PHPUnit Test']],
                    2 => ['text' => '"mi mi mi mi mi!"', 'cite' => [0 => 'PHPUnit Test']],
                    3 => ['text' => 'mi mi mi?', 'cite' => [0 => 'PHPUnit Test',]],
                    4 => ['text' => "mi mi mi mi mi mi.", 'cite' => [0 => 'PHPUnit Test',]],
                    5 => ['text' => "\u{2018}mi mi mi?\u{2019}", 'cite' => [0 => 'PHPUnit Test',]],
                    6 => ['text' => "\u{201C}mi mi m’i mi.\u{201D}", 'cite' => [0 => 'PHPUnit Test',]],
                    7 => ['text' => "‘mi mi m’i mi.’", 'cite' => [0 => 'PHPUnit Test',]],
                    8 => ['text' => "“mi mi mi mi mi.”", 'cite' => [0 => 'PHPUnit Test',]],
                    9 => ['text' => "mi mi mi, mi mi mi mi mi mi.", 'cite' => [0 => 'PHPUnit Test',]],
                ]]]
            ],
            "\u{2018}\u{2019}"=> [
                'input'=>"mi: mi mi mi? 'mi; mi mi mi.' \"mi mi mi mi mi!\" mi mi mi? mi mi mi mi mi mi.  \u{2018}mi mi mi?\u{2019} \u{201C}mi mi m’i mi.\u{201D} ‘mi mi m’i mi.’ “mi mi mi mi mi.” mi mi mi, mi 'mi m'i mi' mi mi.",
                'expected'=>['mi' => [1 => [
                    0 => ['text' => 'mi: mi mi mi?', 'cite' => [0 => 'PHPUnit Test',]],
                    1 => ['text' => "'mi; mi mi mi.'", 'cite' => [0 => 'PHPUnit Test']],
                    2 => ['text' => '"mi mi mi mi mi!"', 'cite' => [0 => 'PHPUnit Test']],
                    3 => ['text' => 'mi mi mi?', 'cite' => [0 => 'PHPUnit Test',]],
                    4 => ['text' => "mi mi mi mi mi mi.", 'cite' => [0 => 'PHPUnit Test',]],
                    5 => ['text' => "\u{2018}mi mi mi?\u{2019}", 'cite' => [0 => 'PHPUnit Test',]],
                    6 => ['text' => "\u{201C}mi mi m’i mi.\u{201D}", 'cite' => [0 => 'PHPUnit Test',]],
                    7 => ['text' => "‘mi mi m’i mi.’", 'cite' => [0 => 'PHPUnit Test',]],
                    8 => ['text' => "“mi mi mi mi mi.”", 'cite' => [0 => 'PHPUnit Test',]],
                    9 => ['text' => "mi mi mi, mi 'mi m'i mi' mi mi.", 'cite' => [0 => 'PHPUnit Test',]],
                ]]]
                ],
            "\u{201C}\u{201D}"=> [
                'input'=>"A a, \u{201C}a a. a. a a a.\u{201D}",
                'expected'=>['a' => [1 => [
                    ['text' => 'A a, “a a.”', 'cite' => [0 => 'PHPUnit Test',]],
                    ['text' => "a.", 'cite' => [0 => 'PHPUnit Test']],
                    ['text' => '“a a a.”', 'cite' => [0 => 'PHPUnit Test']],
                ]]]
                ],

        ];
    }

    public static function sentences(): array
    {
        return [
            'period_0'=>[
                "Bante le cudu to.",
                [
                    'bante' => [1 => [0 => ['text' => 'Bante le cudu to.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'le' => [1 => [0 => ['text' => 'Bante le cudu to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'cudu' => [1 => [0 => ['text' => 'Bante le cudu to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'to' => [1 => [0 => ['text' => 'Bante le cudu to.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            'period_1'=>[
                "Mi no jixi ku kete.",
                [
                    'mi' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'no' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'jixi' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'ku' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'kete' => [1 => [0 => ['text' => 'Mi no jixi ku kete.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            'period_2'=>[
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
            'period_3'=>[
                "Hin xey sen lil.",
                [
                    'hin' => [1 => [0 => ['text' => 'Hin xey sen lil.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'xey' => [1 => [0 => ['text' => 'Hin xey sen lil.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'sen' => [1 => [0 => ['text' => 'Hin xey sen lil.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'lil' => [1 => [0 => ['text' => 'Hin xey sen lil.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            'period_4'=>[
                "Ete sen bon insan.",
                [
                    'ete' => [1 => [0 => ['text' => 'Ete sen bon insan.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'sen' => [1 => [0 => ['text' => 'Ete sen bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'bon' => [1 => [0 => ['text' => 'Ete sen bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'insan' => [1 => [0 => ['text' => 'Ete sen bon insan.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            'period_5'=>[
                "Multi insan no jixi hinto.",
                [
                    'multi' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'insan' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'no' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'jixi' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'hinto' => [1 => [0 => ['text' => 'Multi insan no jixi hinto.', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            'question_mark_0'=>[
                "Kete le fale to?",
                [
                    'kete' => [1 => [0 => ['text' => 'Kete le fale to?', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'le' => [1 => [0 => ['text' => 'Kete le fale to?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'fale' => [1 => [0 => ['text' => 'Kete le fale to?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'to' => [1 => [0 => ['text' => 'Kete le fale to?', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            'question_mark_1'=>[
                "Keto le okur?",
                [
                    'keto' => [1 => [0 => ['text' => 'Keto le okur?', 'cite' => [0 => 'PHPUnit Test'],],],],
                    'le' => [1 => [0 => ['text' => 'Keto le okur?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'okur' => [1 => [0 => ['text' => 'Keto le okur?', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            'question_mark_2'=>[
                "Hinto sen keto?",
                [
                    'hinto' => [1 => [0 => ['text' => 'Hinto sen keto?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'sen' => [1 => [0 => ['text' => 'Hinto sen keto?', 'cite' => [0 => 'PHPUnit Test',],],],],
                    'keto' => [1 => [0 => ['text' => 'Hinto sen keto?', 'cite' => [0 => 'PHPUnit Test',],],],],
                ],
            ],
            'comma'=>[
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
        ];
    }
}