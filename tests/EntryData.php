<?php

declare(strict_types=1);

class EntryData
{

    static public function get_final_coda() : array
    {
        return [
            'o'             => [['syllables'=>['o']], ''],
            'in'            => [['syllables'=>['in']], 'n'],
            'na'            => [['syllables'=>['na']], ''],
            'ata'           => [['syllables'=>['a', 'ta']], ''],
            'bala'          => [['syllables'=>['ba', 'la']], ''],
            'pingo'         => [['syllables'=>['pin', 'go']], ''],
            'bonglu'        => [['syllables'=>['bon', 'glu']], ''],
            'atryum'        => [['syllables'=>['at', 'ryum']], 'm'],
            'vodka'         => [['syllables'=>['vod', 'ka']], ''],
            'hervatska'     => [['syllables'=>['her', 'vats', 'ka']], ''],
            'biomekanilog'  => [['syllables'=>['bi', 'o', 'me', 'ka', 'ni', 'log']], 'g'],
        ];
    }
    static public function get_final_vowel()
    {
        return [
            'o'             => [['syllables'=>['o']], 'o'],
            'in'            => [['syllables'=>['in']], 'i'],
            'na'            => [['syllables'=>['na']], 'a'],
            'ata'           => [['syllables'=>['a', 'ta']], 'a'],
            'bala'          => [['syllables'=>['ba', 'la']], 'a'],
            'pingo'         => [['syllables'=>['pin', 'go']], 'o'],
            'bonglu'        => [['syllables'=>['bon', 'glu']], 'u'],
            'atryum'        => [['syllables'=>['at', 'ryum']], 'u'],
            'vodka'         => [['syllables'=>['vod', 'ka']], 'a'],
            'hervatska'     => [['syllables'=>['her', 'vats', 'ka']], 'a'],
            'biomekanilog'  => [['syllables'=>['bi', 'o', 'me', 'ka', 'ni', 'log']], 'o'],
        ];
    }

    static public function get_final_syllable(): array
    {
        return [
            'o'             => [['syllables'=>['o']], 'o'],
            'ata'           => [['syllables'=>['a', 'ta']], 'ta'],
            'bonglu'        => [['syllables'=>['bon', 'glu']], 'glu'],
            'atryum'        => [['syllables'=>['at', 'ryum']], 'ryum'],
            'hervatska'     => [['syllables'=>['her', 'vats', 'ka']], 'ka'],
            'biomekanilog'  => [['syllables'=>['bi', 'o', 'me', 'ka', 'ni', 'log']], 'log'],
        ];
    }

        static public function get_penult_coda(): array
    {
        return [
            'o'             => [['syllables'=>['o']], ''],
            'in'            => [['syllables'=>['in']], ''],
            'na'            => [['syllables'=>['na']], ''],
            'ata'           => [['syllables'=>['a', 'ta']], ''],
            'bala'          => [['syllables'=>['ba', 'la']], ''],
            'pingo'         => [['syllables'=>['pin', 'go']], 'n'],
            'bonglu'        => [['syllables'=>['bon', 'glu']], 'n'],
            'atryum'        => [['syllables'=>['at', 'ryum']], 't'],
            'vodka'         => [['syllables'=>['vod', 'ka']], 'd'],
            'hervatska'     => [['syllables'=>['her', 'vats', 'ka']], 'ts'],
        ];
    }

    static public function get_penult_vowel(): array
    {
        return [
            'o'             => [['syllables'=>['o']], ''],
            'in'            => [['syllables'=>['in']], ''],
            'na'            => [['syllables'=>['na']], ''],
            'ata'           => [['syllables'=>['a', 'ta']], 'a'],
            'bala'          => [['syllables'=>['ba', 'la']], 'a'],
            'pingo'         => [['syllables'=>['pin', 'go']], 'i'],
            'bonglu'        => [['syllables'=>['bon', 'glu']], 'o'],
            'atryum'        => [['syllables'=>['at', 'ryum']], 'a'],
            'vodka'         => [['syllables'=>['vod', 'ka']], 'o'],
            'hervatska'     => [['syllables'=>['her', 'vats', 'ka']], 'a'],
            'biomekanilog'  => [['syllables'=>['bi', 'o', 'me', 'ka', 'ni', 'log']], 'i'],
        ];
    }

    static public function get_syllables()
    {
        return;
    }

    static public function all_consonants()
    {
        return [
            ['jklmn', true],
            ['wxz', true],
            ['rstv', true],
            ['bcdfg', true],
            ['hjkl', true],
            ['jkalmn', false],
            ['jkelmn', false],
            ['jkilmn', false],
            ['jkolmn', false],
            ['jkpulmn', false],
            ['klykz', true],
        ];
    }



    static public function ends_with_vowel()
    {
        return [
            'a'  => [['term'=>'aeiouklkla'], true],
            'e'  => [['term'=>'aeiouklkle'], true],
            'i'  => [['term'=>'aeiouklkli'], true],
            'o'  => [['term'=>'aeiouklklo'], true],
            'u'  => [['term'=>'aeiouklklu'], true],
            'y(false)'  => [['term'=>'aeiouklkly'], false],
            'l'  => [['term'=>'aeiouklkl'], false],
            'k'  => [['term'=>'aeiouklk'], false],
            'b'  => [['term'=>'aeiouklkb'], false],
            'z'  => [['term'=>'aeiouklkz'], false],
            'no vowel'  => [['term'=>'klkz'], false],
        ];
    }



    // #[DataProvider('EntryDataProvider')]
    public static function syllableProvider(): array
    {
        return [
            'o'             => [['term'=>'o'],        ['o']],
            'in'            => [['term'=>'in'],       ['in']],
            'na'            => [['term'=>'na'],       ['na']],
            'ata'           => [['term'=>'ata'],      ['a', 'ta']],
            'bla'           => [['term'=>'bla'],      ['bla']],
            'max'           => [['term'=>'max'],      ['max']],
            'bala'          => [['term'=>'bala'],     ['ba', 'la']],
            'pingo'         => [['term'=>'pingo'],    ['pin', 'go']],
            'patre'         => [['term'=>'patre'],    ['pa', 'tre']],
            'ultra'         => [['term'=>'ultra'],    ['ul', 'tra']],
            'bonglu'        => [['term'=>'bonglu'],   ['bon', 'glu']],
            'aorta'         => [['term'=>'aorta'],    ['a', 'or', 'ta']],
            'bioyen'        => [['term'=>'bioyen'],   ['bi', 'o', 'yen']],
            'atryum'        => [['term'=>'atryum'],   ['at', 'ryum']],
            'vodka'         => [['term'=>'vodka'],    ['vod', 'ka']],
            'koktel'        => [['term'=>'koktel'],   ['kok', 'tel']],
            'hotdogu'       => [['term'=>'hotdogu'],  ['hot', 'do', 'gu']],
            'hervatska'     => [['term'=>'hervatska'], ['her', 'vats', 'ka']],
            'markse'        => [['term'=>'markse'],   ['mark', 'se']],
            'turkmeni'      => [['term'=>'turkmeni'], ['turk', 'me', 'ni']],
            'awstrali'      => [['term'=>'awstrali'], ['aws', 'tra', 'li']],
            'hertsegovina'  => [['term'=>'hertsegovina'], ['hert', 'se', 'go', 'vi', 'na']],
            'bahrayn'       => [['term'=>'bahrayn'],  ['bah', 'rayn']],
            'ewskal'        => [['term'=>'ewskal'],   ['ews', 'kal']],
            'biokimika'     => [['term'=>'biokimika'],    ['bi', 'o', 'ki', 'mi', 'ka']],
            'biomekanilog'  => [['term'=>'biomekanilog'], ['bi', 'o', 'me', 'ka', 'ni', 'log']],
            'ekonomilogi'   => [['term'=>'ekonomilogi'],  ['e', 'ko', 'no', 'mi', 'lo', 'gi']],
            'estatisti'     => [['term'=>'estatisti'],    ['es', 'ta', 'tis', 'ti']],
            'nyurologi'     => [['term'=>'nyurologi'],    ['nyu', 'ro', 'lo', 'gi']],
            'plantalogi'    => [['term'=>'plantalogi'],   ['plan', 'ta', 'lo', 'gi']],
            'robotitekno'   => [['term'=>'robotitekno'],  ['ro', 'bo', 'ti', 'tek', 'no']],
            'sonzaylogi'    => [['term'=>'sonzaylogi'],   ['son', 'zay', 'lo', 'gi']],
            'sosyallogi'    => [['term'=>'sosyallogi'],   ['so', 'syal', 'lo', 'gi']],
            'syensi'        => [['term'=>'syensi'],       ['syen', 'si']],
            'tenmunlogi'    => [['term'=>'tenmunlogi'],   ['ten', 'mun', 'lo', 'gi']],
            'antiyen'       => [['term'=>'antiyen'],      ['an', 'ti', 'yen']],
            'badminton'     => [['term'=>'badminton'],    ['bad', 'min', 'ton']],
            'konkurexey'    => [['term'=>'konkurexey'],   ['kon', 'ku', 're', 'xey']],
            'kriketo'       => [['term'=>'kriketo'],      ['kri', 'ke', 'to']],
            'kuxtiyen'      => [['term'=>'kuxtiyen'],     ['kux', 'ti', 'yen']],
            'maraton'       => [['term'=>'maraton'],      ['ma', 'ra', 'ton']],
            'mesatenis'     => [['term'=>'mesatenis'],    ['me', 'sa', 'te', 'nis']],
            'pawbuyen'      => [['term'=>'pawbuyen'],     ['paw', 'bu', 'yen']],
            'piklebal'      => [['term'=>'piklebal'],     ['pi', 'kle', 'bal']],
            'polo'          => [['term'=>'polo'],         ['po', 'lo']],
            'rekordi'       => [['term'=>'rekordi'],      ['re', 'kor', 'di']],
        ];
    }
}
