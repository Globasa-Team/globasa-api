<?php

namespace WorldlangDict\API;

use Exception;
use Throwable;
use TypeError;

require_once("models/Word.php");

class Entry_update_controller
{

    // Microseconds (1 millions of a second)
    const TINY_IO_DELAY = 5000; // 5k microseconds = a twohundredths of a second


    /**
     * 
     * Rule 1 & 2: Assumes you are not sending any phrases or affixes as $rhyme!
     */
    private static function add_entry_rhyme_xref(string $entry_slug, string $rhyme_slug)
    {
        global $cfg, $dict;

        if (!$cfg['process_rhymes']) return;

        /* Do not lists affixes, phrases, self  */
        if (
            $dict[$rhyme_slug]['category'] === 'affix' ||  // 1i
            $dict[$rhyme_slug]['category'] === 'phrase' ||  // 1ii
            $entry_slug === $rhyme_slug                    // 2iii
        ) return;

        // Fetch final morphemes
        $entry_final_morpheme = Word::get_final_morpheme($entry_slug);
        $rhyme_final_morpheme = Word::get_final_morpheme($rhyme_slug);

        /* Find possible suffix/root version of rhyme by removing or adding hyphen */
        if ($rhyme_final_morpheme[0] === '-') {
            $rhyme_final_morpheme_alt = substr($rhyme_final_morpheme, 1);
        } else {
            $rhyme_final_morpheme_alt = '-' . $rhyme_final_morpheme;
        }

        if (
            $entry_final_morpheme === $rhyme_final_morpheme ||  // 2i & 2ii
            $entry_final_morpheme === $rhyme_final_morpheme_alt // 3i, 3ii, 3iii
        ) return;


        // If still here, copy data
        $dict[$entry_slug]['rhyme'][$rhyme_slug]['word class'] = $dict[$rhyme_slug]['word class'];
        $dict[$entry_slug]['rhyme'][$rhyme_slug]['term'] = $dict[$rhyme_slug]['term'];
        $dict[$entry_slug]['rhyme'][$rhyme_slug]['term_spec'] = $dict[$rhyme_slug]['term_spec'];
        // Copy all translations
        foreach ($dict[$rhyme_slug]['trans html'] as $lang => $trans) {
            $dict[$entry_slug]['rhyme'][$rhyme_slug][$lang] = $trans;
        }
    }



    /**
     * Calculate stats from the dictionary entries.
     */
    public static function calculate_stats(): void
    {
        global $dict, $stats, $import_report;

        $max_examples = 0;
        $max_examples_term = "";

        \pard\m("Calculate stats");
        $stats['etymology source percent'] = [];
        $stats['natlang roots'] = 0;

        foreach ($dict as $slug => $entry) {

            try {
                if (isset($entry['examples']) && count($entry['examples']) > $max_examples) {
                    $max_examples = count($entry['examples']);
                    $max_examples_term = $entry['slug'];
                }

                if ($entry['category'] === 'root' && array_key_exists('etymology', $entry) && array_key_exists('natlang', $entry['etymology'])) {

                    // Calculate the total number of roots and roots for each source lang
                    $stats['natlang roots'] += 1;

                    foreach ($entry['etymology']['natlang'] as $natlang => $data) {
                        if (!array_key_exists($natlang, $stats['etymology source percent'])) {
                            $stats['etymology source percent'][$natlang] = 0;
                        }
                        $stats['etymology source percent'][$natlang] += 1;
                    }
                } elseif ($entry['category'] === 'root' && array_key_exists('etymology', $entry) && array_key_exists('kwasilexi', $entry['etymology'])) {

                    // Calculate the total number of roots and roots for each source lang
                    $stats['natlang roots'] += 1;

                    foreach ($entry['etymology']['kwasilexi'] as $natlang => $data) {
                        if (!array_key_exists($natlang, $stats['etymology source percent'])) {
                            $stats['etymology source percent'][$natlang] = 0;
                        }
                        $stats['etymology source percent'][$natlang] += 1;
                    }
                }
            } catch (TypeError $e) {
                //$dev_report[];
                $import_report[] = ['term' => $slug, 'msg' => "Major error in `{$slug}` entry. Found in calculate_stats()"];
                \pard\print_throwable($e, "Type error in term `{$slug}`", true);
            }
        }

        // Calculate percentages
        foreach ($stats['etymology source percent'] as $natlang => $count) {
            $stats['etymology source percent'][$natlang] = round($count / $stats['natlang roots'] * 100, 2);
        }
        arsort($stats['etymology source percent']);

        \pard\m("Most examples: " . $max_examples_term . " with " . $max_examples);
    }



    private static function finalize_all_data()
    {
        global $debug_mode;

        if ($debug_mode) return;

        // Insert data that needed all entries to be loaded
        \pard\sec("Finalize entries");
        self::insert_derived_term_xref();
        self::insert_etymology_xrefs();
        self::insert_rhyme_xrefs();
        self::insert_entry_notes_xref();

        \pard\m("Sorting");
        global $tags, $dict, $min_entries, $basic_entries, $standard_entries, $term_indexes, $search_terms;

        foreach ($tags as $tag => $data) {
            ksort($tags[$tag]);
        }

        ksort($min_entries);
        ksort($basic_entries);
        ksort($standard_entries);
        ksort($term_indexes);
        ksort($search_terms);

        \pard\end();
    }




    /**
     * Renders the basic entry for each language. Includes:
     *  term, class, category, translations.
     */
    private static function insert_basic_entry(array $parsed)
    {
        global $basic_entries;

        if (!isset($parsed['trans html'])) {
            \pard\m("Warning: missing trans html on " . $parsed['slug']);
            return;
        }

        foreach ($parsed['trans html'] as $lang => $translation) {
            $basic_entries[$lang][$parsed['slug']] = array();
            $basic_entries[$lang][$parsed['slug']]['term'] = $parsed['term'];
            $basic_entries[$lang][$parsed['slug']]['class'] = $parsed['word class'];
            $basic_entries[$lang][$parsed['slug']]['category'] = $parsed['category'];
            $basic_entries[$lang][$parsed['slug']]['translation'] = $translation;
        }
    }



    /**
     * Insert xref data for the root of derived terms
     */
    public static function insert_derived_term_xref()
    {
        global $cfg, $dict, $import_report, $derived_data;
        \pard\m("Derived terms");

        foreach ($derived_data as $root => $terms) {
            // For each root, find all derived terms
            foreach ($terms as $term) {

                // Skip if word doesn't exist
                if (!array_key_exists($root, $dict)) {
                    $cfg['log']->add("Attempted to link entry `{$root}` to `{$term}`, but it doesn't exist.");
                    $import_report[] = ['term' => $root, 'msg' => "Term missing. Was linking from `{$term}`."];
                    continue;
                }

                // Copy derived term class to root
                $dict[$root]["derived terms"][$term]['class'] = $dict[$term]['word class'];
                $dict[$root]["derived terms"][$term]['term'] = $dict[$term]['term'];

                // Copy derived term translation data to root
                foreach ($dict[$term]['trans html'] as $lang => $translation) {
                    $dict[$root]['derived terms'][$term]['trans'][$lang] = $translation;
                }
            }
        }
    }



    private static function insert_examples(array &$entry)
    {
        global $examples;

        if (!isset($examples[$entry['slug']])) {
            return;
        }

        if (isset($entry['examples'])) {
            $entry['examples'] = array_merge($entry['examples'], $examples[$entry['slug']]);
        } else {
            $entry['examples'] = $examples[$entry['slug']];
        }
    }


    /**
     * Renders minimum definitions for the current term
     * and adds them to the array of mini defs.
     */
    private static function insert_minimum_entry(array $parsed)
    {
        global $min_entries;

        foreach ($parsed['trans html'] as $lang => $trans) {
            $min_entries[$lang][$parsed['slug']] = '<em>(' . $parsed['word class'] . ')</em> ' . $trans;
        }
    }



    /**
     * For each languages, add that languages search terms to the
     * search term array for that languages.
     */
    private static function insert_search_terms(array $parsed)
    {
        global $search_terms;
        foreach ($parsed['search terms'] as $lang => $terms) {
            foreach ($terms as $term) {
                $search_terms[$lang][$term][] = $parsed['slug'];
            }
        }
    }


    /**
     * 
     */
    private static function insert_standard_entry(array $entry)
    {
        global $standard_entries;

        if (!isset($entry['term'])) $entry['term'] = "";
        if (!isset($entry['word class'])) $entry['word class'] = "";
        if (!isset($entry['category'])) $entry['category'] = "";
        if (!isset($entry['trans'])) $entry['trans'] = "";
        if (!isset($entry['etymology'])) $entry['etymology'] = "";

        $standard_entries[$entry['slug']] = [
            'term' => $entry['term'],
            'word class' => $entry['word class'],
            'category' => $entry['category'],
            'trans' => $entry['trans'],
            'etymology' => $entry['etymology']
        ];
    }



    /**
     * Renders tags for the current term
     * and adds them to the array of tags.
     */
    private static function insert_tags(array $parsed)
    {
        global $tags;
        if (array_key_exists('tags', $parsed)) {
            foreach ($parsed['tags'] as $tag) {
                $tags[$tag][] = $parsed['slug'];
            }
        }
    }




    /**
     * For each languages add all term forms (the term and it's alt forms, if any)
     * to the index of all words.
     */
    private static function insert_term_index(array $parsed)
    {
        global $term_index;
        $term_index[$parsed['slug']] = [];
        foreach ($parsed['alt forms'] as $alt) {
            $term_index[$alt] = $parsed['slug'];
        }
    }


    static function validate_entry(&$entry): void
    {
        global $import_report, $dev_report;

        foreach (['term', 'word class', 'category', 'trans'] as $key) {
            if (!array_key_exists($key, $entry)) {
                $import_report[] = ['term' => $entry['slug'], 'msg' => "Invalid: `{$key}` is not just blank, but somehow a null"];
            }
        }
        foreach ($entry as $key => $value) {
            if ($value === null) {
                $import_report[] = ['term' => $entry['slug'], 'msg' => "Invalid: `{$key}` is not just blank, but somehow a null"];
            }
        }
        foreach ($entry['trans html'] as $lang => $translation) {
            if (str_contains($translation, ":</em>")) {
                $import_report[] = ['term' => $entry['slug'], 'msg' => "Invalid: `{$lang}` has colon inside italic rathrer than outside"];
            }
        }
        if ($entry['category'] === 'derived' || $entry['category'] === 'phrase') {
            if (empty($entry['etymology']['derived'])) {
                $import_report[] = ['term' => $entry['slug'], 'msg' => 'Invalid: category is derived or phrase but no derived etymology detected.'];
            }
        }
    }


    static function parse_spreadsheet_data($term_stream)
    {
        global $new_csv_data, $dict, $debug_data, $debug_mode, $cfg;

        // Download the official term list, processing each term.
        $tp = new Term_parser(fields: fgetcsv($term_stream, escape: ""));

        \pard\counter_start("Parsing spreadsheet terms");
        while (($data = fgetcsv($term_stream, escape: "")) !== false) {
            // Parse term if it exists
            if (empty($data) || empty($data[0])) {
                continue;
            }

            [$raw_entry, $entry, $csv_row] = $tp->parse_term($data);

            if (!$debug_mode || in_array($entry['slug'], $cfg['test_entries'])) {
                $new_csv_data[$entry['slug']] = $csv_row;
                $debug_data[$entry['slug']] = $raw_entry;
                if (isset($entry['etymology'][')'])) unset($entry['etymology'][')']);

                // Insert entry in aggregate data
                self::insert_term_index($entry);
                self::insert_search_terms($entry);
                self::insert_basic_entry($entry);
                self::insert_minimum_entry($entry);
                self::insert_standard_entry($entry);

                self::insert_tags(parsed: $entry);
                self::insert_examples($entry);
                self::validate_and_count_category($entry['category'], $entry['term']);
                self::update_rhyme_data($entry);

                self::validate_entry($entry);

                $dict[$entry['slug']] = $entry;
            }
            usleep(SMALL_IO_DELAY);
            \pard\counter_next();
        }

        \pard\counter_end();

        \pard\end();
    }



    /**
     * Compare the new and old word list and log any changes.
     */
    static function log_changes()
    {
        global $cfg, $comparison_option;

        \pard\m("Logging changes");
        if (!$comparison_option) return;
        // Find changes
        $comparison = new Dictionary_comparison();
        // Log changes
        $log = new Dictionary_log($cfg);
        $log->add($comparison->changes);
        $cfg['log']->add("Changes logged: " . count($comparison->changes));
    }




    /**
     * Updates the etymology/derived field to include translations
     * Looks at each entry's `derived` etymology array, eg:
     *      ["term1", "+", "term2", ",", "term3"]
     * and adds in the data from the referenced slugs.
     */
    private static function insert_etymology_xrefs()
    {
        global $dict;
        \pard\m("Derived etymology");

        foreach ($dict as $slug => $entry) {
            if (isset($entry['etymology']['derived'])) {
                foreach ($entry['etymology']['derived'] as $part) {
                    if (strcmp($part, '+') === 0 || strcmp($part, ',') === 0 || !isset($dict[$part])) {
                        // if it's not an entry, just append it
                        $dict[$slug]['etymology']['derived trans'][] = ['text' => $part];
                    } else {
                        $dict[$slug]['etymology']['derived trans'][] = [
                            'slug' => $part,
                            'text' => $dict[$part]['term'] . (!empty($dict[$part]['slug_mod']) ? ' (' . $dict[$part]['slug_mod'] . ')' : ''),
                            'word class' => $dict[$part]['word class'],
                            'trans' => $dict[$part]['trans html']
                        ];
                    }
                }
            }
            if (isset($entry['etymology']['am oko'])) {
                foreach ($entry['etymology']['am oko'] as $ref_slug => $data) {
                    if (isset($dict[$ref_slug])) {
                        $dict[$slug]['etymology']['am oko'][$ref_slug] = $dict[$ref_slug]['term_spec'];
                        if (str_contains($slug, '_')) {
                            \pard\m($entry['etymology']['am oko'], "etymology with _");
                        }
                    } else {
                        \pard\m($slug, "missing entry");
                    }
                }
            }
        }
    }


    static function update_entries(string $current_csv_filename, string $old_csv_filename)
    {
        global $cfg, $old_csv_data, $debug_mode, $comparison_option, $write_files;

        \pard\sec("Update entries");

        // Load old
        if ($comparison_option) {
            load_csv($old_csv_filename, $old_csv_data);
        }

        // Load and parse new data
        \pard\m("Loading current terms");
        $term_stream = fopen($current_csv_filename, "r")
            or throw new Exception("Failed to open " . $current_csv_filename);
        self::parse_spreadsheet_data($term_stream);

        if (!feof($term_stream)) {
            $cfg['log']->add("Unexpected fgetcsv() fail");
        }
        fclose($term_stream);

        // Add cross referenced data to entries
        self::finalize_all_data();

        // Check for changes
        if (!$debug_mode) {
            \pard\sec("Post entry update");
            Entry_update_controller::log_changes();
            Entry_update_controller::calculate_stats();
            \pard\end();
        }

        // Write dictionary files
        if ($write_files)
            File_controller::write_api2_files();
    }

    /**
     * Update notes for all entries with cononical terms
     *
     */
    private static function insert_entry_notes_xref()
    {
        /* TODO: Determine if this is still in use */
        global $dict;

        foreach ($dict as $term => $entry) {
            if (!isset($entry['entry notes'])) continue;

            foreach ($entry['entry notes'] as $keyword => $data) {
                if (
                    $keyword === 'am oko' || $keyword === 'kurto lexi' ||
                    $keyword === 'kompara'
                ) {
                    foreach ($data as $reference => $null_data) {
                        $dict[$term]['entry notes'][$keyword][$reference] = $dict[$reference]['term'];
                    }
                }
            }
        }
    }


    /**
     * Go through each rhyme ending group, and add
     * each term to each term, skipping inappropriate terms
     * as done in `self::add_entry_rhyme()`
     */
    private static function insert_rhyme_xrefs()
    {
        global $cfg, $rhyme_data, $dict;

        if (!$cfg['process_rhymes']) return;

        \pard\m("Update entry rhymes");
        \pard\progress_start(count($rhyme_data), "rhyme groups");

        // Go through each rhyme group to copy rhyming terms in to entry
        foreach ($rhyme_data as $ending_group) {
            \pard\progress_increment();
            // skip if there are no rhymes
            if (count($ending_group) < 2) continue;

            sort($ending_group);

            foreach ($ending_group as $cur_rhyme_slug) {

                if ($dict[$cur_rhyme_slug]['category'] === 'phrase')
                    continue;

                // Add to each partner (ignore self)
                foreach ($ending_group as $rhyme_partner_slug) {
                    self::add_entry_rhyme_xref($cur_rhyme_slug, $rhyme_partner_slug);
                }

                // Generate the alt form (root slug)
                if ($cur_rhyme_slug[0] === '-') {
                    $alt = substr($cur_rhyme_slug, 1);
                } else {
                    $alt = '-' . $cur_rhyme_slug;
                }

                // Fetch entry final morpheme
                $final_morpheme = Word::get_final_morpheme($cur_rhyme_slug);

                if ($final_morpheme[0] === '-') {
                    $alt = substr($final_morpheme, 1);
                } else {
                    $alt = '-' . $final_morpheme;
                }

                if (isset($dict[$alt])) {
                    $dict[$cur_rhyme_slug]['rhyme exclusions'] = [$final_morpheme, $alt];
                } else {
                    $dict[$cur_rhyme_slug]['rhyme exclusions'] = [$final_morpheme];
                }
            }
            usleep(100);
        }
        \pard\progress_end();
    }


    /**
     * Collecting rhyming data. A rhyme is the last two letters matching.
     * If there are no vowels, use 3.
     */
    private static function update_rhyme_data(array $entry): void
    {
        global $rhyme_data;

        $group = strtolower(substr($entry['term'], -2));

        if (!preg_match(GLOBAL_VOWEL_REGEX, $group)) {
            // If it does not have vowels use 3 letters
            $group = substr($entry['slug'], -3);
        }

        $rhyme_data[$group][] = $entry['slug'];
    }




    private static function validate_and_count(string $cat, array &$count_arr)
    {

        if (!isset($count_arr[$cat]))
            $count_arr[$cat] = 1;
        else
            $count_arr[$cat] += 1;
    }



    /**
     * Counts the category     blank: empty(true) !allow(true)
     */
    static function validate_and_count_category(string $cat, string $word)
    {

        global $cfg, $import_report, $category_count;

        if (!in_array($cat, $cfg['valid_categories'])) {
            $cfg['log']->add("Word List Error: Invalid category `$cat` on term `$word`");
            $import_report[] = ['term' => $word, 'msg' => "Word List Error: Invalid category `$cat`"];
        }

        self::validate_and_count($cat, $category_count);
    }
}
