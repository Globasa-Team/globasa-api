<?php

declare(strict_types=1);

class TermParserData
{
    const ETYMOLOGY = 16; // Etymology Index
    static public $csv_headers = [
        'Word', //0
        'slug_mod', //1 new
        'Category', //2
        'WordClass', //3
        'VerbTransitivity',//4 new
        'OfficialWord',//5
        'TranslationEng',//6
        'SearchTermsEng',//7
        'TranslationEpo',//8
        'TranslationSpa',//9
        'TransNote', //10 new?
        'TranslationDeu',//11
        'Synonyms',//12
        'Antonyms',//13
        'Example',//14
        'Tags',//15
        'LexiliAsel',//16
        'See Also', //17 removed
        'TranslationFra',// 18
        'TranslationRus',// 19
        'TranslationZho'// 20
    ];
    static public $csv_data = [
        "a", //0 word
        "", //1 slug mod
        "root", //2 category
        "il", //3 class
        "", //4 transitivity
        "TRUE", //5 official word
        "ah (_denotes surprise or wonder_)", //6 eng
        "", //7 searth
        "ha", //8 epo
        "ah", //9 spa
        "", //10 note
        "ah (_Ausdruck der Überraschung oder der Verwunderung_)",//11 deu
        "",//12 syn
        "",//13 ant
        "",//14 ex
        "",//15 tag
        "Putunhwa (啊 “a”), Englisa (ah), Doycisa (ah), Espanisa (ah), Rusisa (ах “akh”, a “a”)",//16 etymology
        "",//17
        "ah",//18 fra
        "а", //19 rus
        "啊" //20 zho
    ];

    static public $expected_result = [
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
        'etymology' => ['natlang' => [
            'Doycisa' => 'ah',
            'Englisa' => 'ah',
            'Espanisa' => 'ah',
            'Rusisa' => 'ах &ldquo;akh&rdquo;, a &ldquo;a&rdquo;',
            'Putunhwa' => '啊 &ldquo;a&rdquo;',
        ]],
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
            'eng' => [''=>["ah (<em>denotes surprise or wonder</em>)"]],
            'epo' => [''=>["ha"]],
            'spa' => [''=>["ah"]],
            'deu' => [''=>["ah (<em>Ausdruck der Überraschung oder der Verwunderung</em>)"]],
            'fra' => [''=>["ah"]],
            'rus' => [''=>["а"]],
            'zho' => [''=>["啊"]],
        ],
    ];


    
    static public $entries = [
        'a'=> [
            'csv' => [
                "a",
                "",
                "root",
                "il",
                "",
                "TRUE",
                "ah (_denotes surprise or wonder_)",
                "",
                "ha",
                "ah",
                "",
                "ah (_Ausdruck der Überraschung oder der Verwunderung_)",
                "",
                "",
                "",
                "",
                "Putunhwa (啊 “a”), Englisa (ah), Doycisa (ah), Espanisa (ah), Rusisa (ах “akh”, a “a”)",
                "",
                "ah",
                "а",
                "啊"
            ],
            'parsed' => [
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
                    'eng' => [''=>["ah (<em>denotes surprise or wonder</em>)"]],
                    'epo' => [''=>["ha"]],
                    'spa' => [''=>["ah"]],
                    'deu' => [''=>["ah (<em>Ausdruck der Überraschung oder der Verwunderung</em>)"]],
                    'fra' => [''=>["ah"]],
                    'rus' => [''=>["а"]],
                    'zho' => [''=>["啊"]],
                ],
                'trans_v2'=> [
                    'eng' => [''=>["ah (<em>denotes surprise or wonder</em>)"]],
                    'epo' => [''=>["ha"]],
                    'spa' => [''=>["ah"]],
                    'deu' => [''=>["ah (<em>Ausdruck der Überraschung oder der Verwunderung</em>)"]],
                    'fra' => [''=>["ah"]],
                    'rus' => [''=>["а"]],
                    'zho' => [''=>["啊"]],
                ],
            ]
        ],
        // devtest_2
        'devtest_2'=> [
            'csv'=>[
                'devtest',
                '2',
                'derived',
                'b',
                '',
                'FALSE',
                'devtest, devtest2',
                '',
                'devtest, devtest2',
                '(_sin uso_)',
                '',
                '',
                '',
                '',
                '',
                '',
                'devtest + devtest_1',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
            'parsed'=>[]
        ],
        //devtest_1
        'devtest_1'=>[
            'csv'=>[
                'devtest',
                '1',
                'derived',
                'b',
                '',
                'FALSE',
                'devtest, devtest1',
                '',
                'devtest, devtest1',
                '(_sin uso_)',
                '',
                '',
                '',
                '',
                '',
                '',
                'devtest + devtest_2',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
            'parsed'=>[]
        ],
        //devtest
        'devtest'=>[
            'csv'=>[
                'Devtest',
                '',
                'root',
                'b',
                '',
                'FALSE',
                'devtest, devtest0',
                '',
                'devtest, devtest0',
                '(_sin uso_)',
                '',
                '',
                '',
                '',
                '',
                '',
                'devtest_1 + devtest_2',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
            'parsed'=>[]
            ]
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
        $data['natlang encloser error'] = [
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
            'expected' => ['etymology' => ['am oko' => ['_1-1_'=>'_1-1_']]]
        ];

        // Tripple comma
        $input = self::$csv_data;
        $input[self::ETYMOLOGY] = "Am oko _2-1_, _2-2_, _2-3_";
        $data['am oko double'] = [
            'csv_data' => $input,
            'expected' => ['etymology' => ['am oko' => ['_2-1_'=>'_2-1_', '_2-2_'=>'_2-2_', '_2-3_'=>'_2-3_']]]
        ];

        // Comma and ji
        $input = self::$csv_data;
        $input[self::ETYMOLOGY] = "Am oko _3-1_, _3-2_ ji _3-3_";
        $data['am oko tripple'] = [
            'csv_data' => $input,
            'expected' => ['etymology' => ['am oko' => ['_3-1_'=>'_3-1_', '_3-2_ ji _3-3_'=>'_3-2_ ji _3-3_']]]
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
