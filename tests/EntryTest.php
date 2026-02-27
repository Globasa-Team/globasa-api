<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use WorldlandDict\API\Entry;


require_once(__DIR__ . "/../models/Entry.php");
require_once(__DIR__ . "/../src/polyfill.php");
require_once(__DIR__ . '/EntryData.php');

final class EntryTest extends TestCase
{
    #[DataProviderExternal(EntryData::class, 'syllableProvider')]
    public function testGetSyllables(string $input, array $expected): void
    {
        // var_dump($input);
        // var_dump($expected);
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::get_syllables($input)
        );
    }

    #[DataProviderExternal(EntryData::class, 'ends_with_vowel')]
    public function testEndsWithVowel(string $term, bool $expected): void
    {
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::ends_with_vowel($term)
        );
    }


    #[DataProviderExternal(EntryData::class, 'all_consonants')]
    public function testAllConsonants(string $term, bool $expected): void
    {
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::all_consonants($term)
        );
    }


    #[DataProviderExternal(EntryData::class, 'get_final_coda')]
    public function testGetFinalCoda(array $entry): void {}


    #[DataProviderExternal(EntryData::class, 'get_final_vowel')]
    public function testGetFinalVowel(array $entry): void {}



    #[DataProviderExternal(EntryData::class, 'get_syllables')]
    public function get_final_morpheme(string $term): void {}

}
