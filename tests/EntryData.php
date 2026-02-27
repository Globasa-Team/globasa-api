<?php

declare(strict_types=1);

class EntryData
{


    static public function get_final_coda()
    {
        return [
            ['sadf', 'asdf']
        ];
    }
    static public function get_final_vowel()
    {
        return;
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
            'a'  => ['aeiouklkla', true],
            'e'  => ['aeiouklkle', true],
            'i'  => ['aeiouklkli', true],
            'o'  => ['aeiouklklo', true],
            'u'  => ['aeiouklklu', true],
            'y(false)'  => ['aeiouklkly', false],
            'l'  => ['aeiouklkl', false],
            'k'  => ['aeiouklk', false],
            'b'  => ['aeiouklkb', false],
            'z'  => ['aeiouklkz', false],
            'no vowel'  => ['klkz', false],
        ];
    }



    // #[DataProvider('EntryDataProvider')]
    public static function syllableProvider(): array
    {
        return [
            'o'             => ['o',        ['o']],
            'in'            => ['in',       ['in']],
            'na'            => ['na',       ['na']],
            'ata'           => ['ata',      ['a', 'ta']],
            'bla'           => ['bla',      ['bla']],
            'max'           => ['max',      ['max']],
            'bala'          => ['bala',     ['ba', 'la']],
            'pingo'         => ['pingo',    ['pin', 'go']],
            'patre'         => ['patre',    ['pa', 'tre']],
            'ultra'         => ['ultra',    ['ul', 'tra']],
            'bonglu'        => ['bonglu',   ['bon', 'glu']],
            'aorta'         => ['aorta',    ['a', 'or', 'ta']],
            'bioyen'        => ['bioyen',   ['bi', 'o', 'yen']],
            'atryum'        => ['atryum',   ['at', 'ryum']],
            'vodka'         => ['vodka',    ['vod', 'ka']],
            'koktel'        => ['koktel',   ['kok', 'tel']],
            'hotdogu'       => ['hotdogu',  ['hot', 'do', 'gu']],
            'hervatska'     => ['hervatska', ['her', 'vats', 'ka']],
            'markse'        => ['markse',   ['mark', 'se']],
            'turkmeni'      => ['turkmeni', ['turk', 'me', 'ni']],
            'awstrali'      => ['awstrali', ['aws', 'tra', 'li']],
            'hertsegovina'  => ['hertsegovina', ['hert', 'se', 'go', 'vi', 'na']],
            'bahrayn'       => ['bahrayn',  ['bah', 'rayn']],
            'ewskal'        => ['ewskal',   ['ews', 'kal']],
            'biokimika'     => ['biokimika',    ['bi', 'o', 'ki', 'mi', 'ka']],
            'biomekanilog'  => ['biomekanilog', ['bi', 'o', 'me', 'ka', 'ni', 'log']],
            'ekonomilogi'   => ['ekonomilogi',  ['e', 'ko', 'no', 'mi', 'lo', 'gi']],
            'estatisti'     => ['estatisti',    ['es', 'ta', 'tis', 'ti']],
            'nyurologi'     => ['nyurologi',    ['nyu', 'ro', 'lo', 'gi']],
            'plantalogi'    => ['plantalogi',   ['plan', 'ta', 'lo', 'gi']],
            'robotitekno'   => ['robotitekno',  ['ro', 'bo', 'ti', 'tek', 'no']],
            'sonzaylogi'    => ['sonzaylogi',   ['son', 'zay', 'lo', 'gi']],
            'sosyallogi'    => ['sosyallogi',   ['so', 'syal', 'lo', 'gi']],
            'syensi'        => ['syensi',       ['syen', 'si']],
            'tenmunlogi'    => ['tenmunlogi',   ['ten', 'mun', 'lo', 'gi']],
            'antiyen'       => ['antiyen',      ['an', 'ti', 'yen']],
            'badminton'     => ['badminton',    ['bad', 'min', 'ton']],
            'konkurexey'    => ['konkurexey',   ['kon', 'ku', 're', 'xey']],
            'kriketo'       => ['kriketo',      ['kri', 'ke', 'to']],
            'kuxtiyen'      => ['kuxtiyen',     ['kux', 'ti', 'yen']],
            'maraton'       => ['maraton',      ['ma', 'ra', 'ton']],
            'mesatenis'     => ['mesatenis',    ['me', 'sa', 'te', 'nis']],
            'pawbuyen'      => ['pawbuyen',     ['paw', 'bu', 'yen']],
            'piklebal'      => ['piklebal',     ['pi', 'kle', 'bal']],
            'polo'          => ['polo',         ['po', 'lo']],
            'rekordi'       => ['rekordi',      ['re', 'kor', 'di']],

        ];
    }
}
