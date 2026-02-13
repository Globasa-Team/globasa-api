<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use WorldlandDict\API\Entry;

require_once(__DIR__ . "/../models/Entry.php");
require_once(__DIR__ . "/../src/polyfill.php");

final class EntryTest extends TestCase
{
    #[DataProvider('syllableProvider')]
    public function testGetSyllables(string $input, array $expected):void {
        // var_dump($input);
        // var_dump($expected);
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::get_syllables($input)
        );
    }

    public static function syllableProvider(): array
    {
        return [
            'o'             => ['o',        ['o']],
            'in'            => ['in',       ['in']],
            'na'            => ['na',       ['na']],
            'ata'           => ['ata',      ['a','ta']],
            'bla'           => ['bla',      ['bla']],
            'max'           => ['max',      ['max']],
            'bala'          => ['bala',     ['ba','la']],
            'pingo'         => ['pingo',    ['pin','go']],
            'patre'         => ['patre',    ['pa','tre']],
            'ultra'         => ['ultra',    ['ul','tra']],
            'bonglu'        => ['bonglu',   ['bon','glu']],
            'aorta'         => ['aorta',    ['a','or','ta']],
            'bioyen'        => ['bioyen',   ['bi','o','yen']],
            'atryum'        => ['atryum',   ['at','ryum']],
            'vodka'         => ['vodka',    ['vod','ka']],
            'koktel'        => ['koktel',   ['kok','tel']],
            'hotdogu'       => ['hotdogu',  ['hot','do','gu']],
            'hervatska'     => ['hervatska',['her','vats','ka']],
            'markse'        => ['markse',   ['mark','se']],
            'turkmeni'      => ['turkmeni', ['turk','me','ni']],
            'awstrali'      => ['awstrali', ['aws','tra','li']],
            'hertsegovina'  => ['hertsegovina',['hert','se','go','vi','na']],
            'bahrayn'       => ['bahrayn',  ['bah','rayn']],
            'ewskal'        => ['ewskal',   ['ews','kal']],
            'baharilogi'    => ['baharilogi',   ['ba','ha','ri','lo','gi']],
            'basalogi'      => ['basalogi',     ['ba','sa','lo','gi']],
            'biokimika'     => ['biokimika',    ['bi','o','ki','mi','ka']],
            'biologi'       => ['biologi',      ['bi','o','lo','gi']],
            'biomekanilog'  => ['biomekanilog', ['bi','o','me','ka','ni','log']],
            'biotekno'      => ['biotekno',     ['bi','o','tek','no']],
            'ekologi'       => ['ekologi',      ['e','ko','lo','gi']],
            'ekonomilogi'   => ['ekonomilogi',  ['e','ko','no','mi','lo','gi']],
            'estatisti'     => ['estatisti',    ['es','ta','tis','ti']],
            'fisika'        => ['fisika',       ['fi','si','ka']],
            'geografi'      => ['geografi',     ['ge','o','gra','fi']],
            'geologi'       => ['geologi',      ['ge','o','lo','gi']],
            'hawanavilogi'  => ['hawanavilogi', ['ha','wa','na','vi','lo','gi']],
            'hewanlogi'     => ['hewanlogi',    ['he','wan','lo','gi']],
            'histori'       => ['histori',      ['his','to','ri']],
            'insanlogi'     => ['insanlogi',    ['in','san','lo','gi']],
            'jismulogi'     => ['jismulogi',    ['jis','mu','lo','gi']],
            'kimika'        => ['kimika',       ['ki','mi','ka']],
            'koncunlogi'    => ['koncunlogi',   ['kon','cun','lo','gi']],
            'kosmologi'     => ['kosmologi',    ['kos','mo','lo','gi']],
            'lefeatrelogi'  => ['lefeatrelogi', ['le','fe','a','tre','lo','gi']],
            'legalogi'      => ['legalogi',     ['le','ga','lo','gi']],
            'mahilogi'      => ['mahilogi',     ['ma','hi','lo','gi']],
            'matemati'      => ['matemati',     ['ma','te','ma','ti']],
            'mekanilogi'    => ['mekanilogi',   ['me','ka','ni','lo','gi']],
            'metallogi'     => ['metallogi',    ['me','tal','lo','gi']],
            'mikrobiologi'  => ['mikrobiologi', ['mi','kro','bi','o','lo','gi']],
            'minxilogi'     => ['minxilogi',    ['min','xi','lo','gi']],
            'mitologi'      => ['mitologi',     ['mi','to','lo','gi']],
            'nyurologi'     => ['nyurologi',    ['nyu','ro','lo','gi']],
            'pesalogi'      => ['pesalogi',     ['pe','sa','lo','gi']],
            'piulogi'       => ['piulogi',      ['pi','u','lo','gi']],
            'plantalogi'    => ['plantalogi',   ['plan','ta','lo','gi']],
            'politi'        => ['politi',       ['po','li','ti']],
            'robotilogi'    => ['robotilogi',   ['ro','bo','ti','lo','gi']],
            'robotitekno'   => ['robotitekno',  ['ro','bo','ti','tek','no']],
            'sikologi'      => ['sikologi',     ['si','ko','lo','gi']],
            'sofilogi'      => ['sofilogi',     ['so','fi','lo','gi']],
            'sonzaylogi'    => ['sonzaylogi',   ['son','zay','lo','gi']],
            'sosyallogi'    => ['sosyallogi',   ['so','syal','lo','gi']],
            'sotilogi'      => ['sotilogi',     ['so','ti','lo','gi']],
            'syensi'        => ['syensi',       ['syen','si']],
            'tenmunlogi'    => ['tenmunlogi',   ['ten','mun','lo','gi']],
            'tutumlogi'     => ['tutumlogi',    ['tu','tum','lo','gi']],
            'antiyen'       => ['antiyen',      ['an','ti','yen']],
            'atleti'        => ['atleti',       ['at','le','ti']],
            'atletiyen'     => ['atletiyen',    ['at','le','ti','yen']],
            'badminton'     => ['badminton',    ['bad','min','ton']],
            'basibol'       => ['basibol',      ['ba','si','bol']],
            'basketobol'    => ['basketobol',   ['bas','ke','to','bol']],
            'bilyardi'      => ['bilyardi',     ['bi','lyar','di']],
            'boksiyen'      => ['boksiyen',     ['bok','si','yen']],
            'bowlinbol'     => ['bowlinbol',    ['bow','lin','bol']],
            'bowlinyen'     => ['bowlinyen',    ['bow','li','nyen']],
            'dayviyen'      => ['dayviyen',     ['day','vi','yen']],
            'dusuyon'       => ['dusuyon',      ['du','su','yon']],
            'eskeytiyen'    => ['eskeytiyen',   ['es','key','ti','yen']],
            'eskiyen'       => ['eskiyen',      ['es','ki','yen']],
            'esportuyen'    => ['esportuyen',   ['es','por','tu','yen']],
            'esprintuyen'   => ['esprintuyen',  ['es','prin','tu','yen']],
            'futbal'        => ['futbal',       ['fut','bal']],
            'gimnasti'      => ['gimnasti',     ['gim','nas','ti']],
            'gimnastidom'   => ['gimnastidom',  ['gim','nas','ti','dom']],
            'gimnastiyen'   => ['gimnastiyen',  ['gim','nas','ti','yen']],
            'golfu'         => ['golfu',        ['gol','fu']],
            'golje'         => ['golje',        ['gol','je']],
            'golmon'        => ['golmon',       ['gol','mon']],
            'hantabol'      => ['hantabol',     ['han','ta','bol']],
            'hoki'          => ['hoki',         ['ho','ki']],
            'hokiyen'       => ['hokiyen',      ['ho','ki','yen']],
            'hurakef'       => ['hurakef',      ['hu','ra','kef']],
            'karate'        => ['karate',       ['ka','ra','te']],
            'konkurexey'    => ['konkurexey',   ['kon','ku','re','xey']],
            'konkureyen'    => ['konkureyen',   ['kon','ku','re','yen']],
            'kriketo'       => ['kriketo',      ['kri','ke','to']],
            'kuxtiyen'      => ['kuxtiyen',     ['kux','ti','yen']],
            'maraton'       => ['maraton',      ['ma','ra','ton']],
            'mesatenis'     => ['mesatenis',    ['me','sa','te','nis']],
            'pawbuyen'      => ['pawbuyen',     ['paw','bu','yen']],
            'piklebal'      => ['piklebal',     ['pi','kle','bal']],
            'polo'          => ['polo',         ['po','lo']],
            'rekordi'       => ['rekordi',      ['re','kor','di']],
            'rugbi'         => ['rugbi',        ['rug','bi']],
            'rugbiyen'      => ['rugbiyen',     ['rug','bi','yen']],
            'tenis'         => ['tenis',        ['te','nis']],
            'tenisyen'      => ['tenisyen',     ['te','ni','syen']],

        ];
    }
}