<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;

require_once(__DIR__ . "/../models/Term_parser.php");
require_once(__DIR__ . "/../models/Entry.php");
require_once(__DIR__ . "/../src/polyfill.php");
require_once(__DIR__ . '/EntryData.php');

final class EntryTest extends TestCase
{

    #[DataProviderExternal(EntryData::class, 'all_consonants')]
    public function test_All_Consonants(string $text, bool $expected): void
    {
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::all_consonants($text)
        );
    }


    #[DataProviderExternal(EntryData::class, 'ends_with_vowel')]
    public function test_Ends_With_Vowel(array $entry, bool $expected): void
    {
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::ends_with_vowel($entry)
        );
    }

    
    #[DataProviderExternal(EntryData::class, 'get_final_coda')]
    public function test_Get_Final_Coda(array $entry, string $expected): void
    {
        // TODO: this should need syllables instead
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::get_final_coda($entry)
        );
    }


    #[DataProviderExternal(EntryData::class, 'get_final_syllable')]
    public function test_Get_Final_Syllable(array $entry, string $expected): void
    {
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::get_final_syllable($entry)
        );
    }

    #[DataProviderExternal(EntryData::class, 'get_final_vowel')]
    public function test_Get_Final_Vowel(array $entry, string $expected): void
    {
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::get_final_vowel($entry)
        );
    }

    #[DataProviderExternal(EntryData::class, 'get_penult_coda')]
    public function test_Get_Penult_Coda(array $entry, string $expected): void
    {
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::get_penult_coda($entry)
        );
    }

    #[DataProviderExternal(EntryData::class, 'get_penult_vowel')]
    public function test_Get_Penult_Vowel(array $entry, string $expected): void
    {
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::get_penult_vowel($entry)
        );
    }

    #[DataProviderExternal(EntryData::class, 'syllableProvider')]
    public function test_Get_Syllables(array $entry, array $expected): void
    {
        $this->assertEquals(
            $expected,
            WorldlangDict\API\Entry::get_syllables($entry)
        );
    }

}