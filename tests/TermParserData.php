<?php

declare(strict_types=1);

class TermParserData
{
    const ETYMOLOGY = 13; // Etymology Index
    static public $csv_headers = [
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

    static public $csv_data = [
        "a",
        "root",
        "il",
        "TRUE",
        "ah (_denotes surprise or wonder_)",
        "",
        "ha",
        "ah",
        "ah (_Ausdruck der Überraschung oder der Verwunderung_)",
        "",
        "",
        "",
        "",
        "Putunhwa (啊 “a”), Englisa (ah), Doycisa (ah), Espanisa (ah), Rusisa (ах “akh”, a “a”)",
        "ah",
        "а",
        "啊"
    ];

    static public $expected_result = [
        'etymology' => ['natlang' => [
            'Doycisa' => 'ah',
            'Englisa' => 'ah',
            'Espanisa' => 'ah',
            'Rusisa' => 'ах &ldquo;akh&rdquo;, a &ldquo;a&rdquo;',
            'Putunhwa' => '啊 &ldquo;a&rdquo;',
        ]],
        'term' => 'a',
        'slug_mod' => '',
        'alt forms' => [],
        'search terms' => [
            'wld' => ['a'],
            'eng' => ['ah'],
            'epo' => ['ha'],
            'spa' => ['ah'],
            'deu' => ['ah'],
            'fra' => ['ah'],
            'rus' => ['а'],
            'zho' => ['啊'],
        ],
        'ipa link' => 'https://ipa-reader.com/?voice=Ewa&text=ˈa',
        'word class' => 'il',
        'trans html' => [
            'eng' => "ah (<em>denotes surprise or wonder</em>)",
            'epo' => "ha",
            'spa' => "ah",
            'deu' => "ah (<em>Ausdruck der &Uuml;berraschung oder der Verwunderung</em>)",
            'fra' => "ah",
            'rus' => "а",
            'zho' => "啊",
        ],
        'slug' => 'a',
        'term_spec' => 'a',
        'ipa' => 'ˈa',
        'status' => true,
        'category' => 'root',
        'trans' => [
            'eng' => [["ah (<em>denotes surprise or wonder</em>)"]],
            'epo' => [["ha"]],
            'spa' => [["ah"]],
            'deu' => [["ah (<em>Ausdruck der Überraschung oder der Verwunderung</em>)"]],
            'fra' => [["ah"]],
            'rus' => [["а"]],
            'zho' => [["啊"]],
        ],
    ];

    public static function etymologyData(): array
    {
        $data = array();


        $data['natlang'] = [
            'csv_data' => self::$csv_data,
            'expected' => ['etymology' => ['natlang' => [
                "Putunhwa" => "啊 &ldquo;a&rdquo;",
                "Englisa" => "ah",
                "Doycisa" => "ah",
                "Espanisa" => "ah",
                "Rusisa" => "ах &ldquo;akh&rdquo;, a &ldquo;a&rdquo;"
            ]]],
        ];

        $csv_data = self::$csv_data;
        $csv_data[self::ETYMOLOGY] = 'Englisa, Rusisa, Klingon (test)';
        $data['natlang with no example'] = [
            'csv_data' => $csv_data,
            'expected' => ['etymology' => ['natlang' => ['Englisa' => "", 'Rusisa' => "", 'Klingon' => "test"]]],
        ];

        $csv_data = self::$csv_data;
        $csv_data[self::ETYMOLOGY] = 'Englisa(, Rusisa, Klingon test';
        $data['natlang encloser error !LOGGED?!'] = [
            'csv_data' => $csv_data,
            'expected' => ['etymology' => ['natlang' => ['Englisa(, Rusisa, Klingon test' => '']]],
        ];

        $input = self::$csv_data;
        $input[self::ETYMOLOGY] = 'https://example.com';
        $output = ['etymology' => ['link' => 'https://example.com']];
        $data['link'] = [
            'csv_data' => $input,
            'expected' => $output
        ];


        // Single item
        $input = self::$csv_data;
        $input[self::ETYMOLOGY] = "Am oko _1-1_";
        $data['am oko single'] = [
            'csv_data' => $input,
            'expected' => ['etymology' => ['am oko' => ['_1-1_']]]
        ];

        // Tripple comma
        $input = self::$csv_data;
        $input[self::ETYMOLOGY] = "Am oko _2-1_, _2-2_, _2-3_";
        $data['am oko double'] = [
            'csv_data' => $input,
            'expected' => ['etymology' => ['am oko' => ['_2-1_', '_2-2_', '_2-3_']]]
        ];

        // Comma and ji
        $input = self::$csv_data;
        $input[self::ETYMOLOGY] = "Am oko _3-1_, _3-2_ ji _3-3_";
        $data['am oko tripple'] = [
            'csv_data' => $input,
            'expected' => ['etymology' => ['am oko' => ['_3-1_', '_3-2_ ji _3-3_']]]
        ];


        $input = self::$csv_data;
        $input[self::ETYMOLOGY] = "Am oko _ji_";
        $data['am oko tripple ji'] = [
            'csv_data' => $input,
            'expected' => ['etymology' => ['am oko' => ['_ji_' => "_ji_"]]]
        ];


        return $data;
    }
}
