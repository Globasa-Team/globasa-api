<?php
namespace globasa_api;
use Exception;
use Throwable;

class Word_list {

    // Microseconds (1 millions of a second)
    const TINY_IO_DELAY = 5000; // 5k microseconds = a twohundredths of a second

    const VALID_WORD_CATEGORIES = array(
        'root', 'proper word', 'derived', 'phrase', 'affix'
    );



    /**
     * Calculate stats from the dictionary entries.
     */
    public static function calculate_stats(): void {
        global $dict, $stats;

        $max_examples = 0;
        $max_examples_term = "";

        pard("calculate stats");
        $stats['etymology source percent'] = [];
        $stats['natlang roots'] = 0;

        foreach($dict as $slug=>$entry) {
            
            if (isset($entry['examples']) && count($entry['examples']) > $max_examples) {
                $max_examples = count($entry['examples']);
                $max_examples_term = $entry['slug'];
            }

            if ($entry['category'] === 'root' && array_key_exists('natlang', $entry['etymology']) ) {

                // Calculate the total number of roots and roots for each source lang
                $stats['natlang roots'] += 1;

                foreach($entry['etymology']['natlang'] as $natlang => $data) {
                    if (!array_key_exists($natlang, $stats['etymology source percent'])) {
                        $stats['etymology source percent'][$natlang] = 0;
                    }
                    $stats['etymology source percent'][$natlang] += 1;
                }
            } elseif ($entry['category'] === 'root' && array_key_exists('kwasilexi', $entry['etymology']) ) {
    
                // Calculate the total number of roots and roots for each source lang
                $stats['natlang roots'] += 1;

                foreach($entry['etymology']['kwasilexi'] as $natlang => $data) {
                    if (!array_key_exists($natlang, $stats['etymology source percent'])) {
                        $stats['etymology source percent'][$natlang] = 0;
                    }
                    $stats['etymology source percent'][$natlang] += 1;
                }
            }

        }

        // Calculate percentages
        foreach($stats['etymology source percent'] as $natlang=>$count) {
            $stats['etymology source percent'][$natlang] = round($count/$stats['natlang roots']*100, 2);
        }
        arsort($stats['etymology source percent']);

        pard("Most examples: ".$max_examples_term." with ".$max_examples_term);
    }




    /**
     * Backlinks
     */
    public static function insert_derived_terms(array &$entries, array &$derived_terms, $config) {
        global $parse_report;
        
        foreach($derived_terms as $root=>$terms) {
            // For each root, find all derived terms
            foreach($terms as $term) {

                // Skip if word doesn't exist
                if (!array_key_exists($root, $entries)) {
                    $config['log']->add("Attempted to link entry `{$root}` to `{$term}`, but it doesn't exist.");
                    $parse_report[] = ['term'=>$root, 'msg'=>"Term missing. Was linking from `{$term}`."];
                    continue;
                }

                // Copy derived term class to root
                $entries[$root]["derived terms"][$term]['class'] = $entries[$term]['word class'];

                // Copy derived term translation data to root
                foreach($entries[$term]['trans html'] as $lang=>$translation) {
                    if (isset($entries[$root])) {
                        $entries[$root]["derived terms"][$term]['trans'][$lang] = $translation;
                    } elseif (!isset($entries[$root])) {
                        // TODO: record or react to non-existent entries?
                    }
                }
            }
        }
    }

    /**
     * Finds terms referenced and inserts translations in to the entry.
     */
    private static function insert_referenced_definition(array &$entries, array &$trans) {
        return;
        foreach($entries as $slug=>$entry) {
            // see also
        }
    }

    /**
     * Compare the new and old word list and log any changes.
     */
    static function log_changes(array &$current_data, array &$old_data, array $c) {
        // Find changes
        $comparison = new Dictionary_comparison($old_data, $current_data, $c);
        // Log changes
        $log = new Dictionary_log($c);
        $log->add($comparison->changes);
        $c['log']->add("Changes logged: ".count($comparison->changes));
    }

    /**
     * Open current CSV, reading line by line, and processing the words
     * individually and writing out dictionary files. This is to reduce max
     * load on the server. A usleep() delay between each term is used.
     */
    public static function load_current_terms(
            array &$parsed_entries,

            array &$min_entries,
            array &$basic_entries,
            
            array &$term_indexes,
            array &$search_terms,
            array &$tags,
            
            array &$natlang_etymologies,
            
            int &$word_count,
            array &$category_count,
            
            array &$debug_data,
            array &$c, string $current_csv_filename
        ) {

        // Download the official term list, processing each term.
        $term_stream = fopen($current_csv_filename, "r")
            or throw new Exception("Failed to open ".$current_csv_filename);
        $tp = new Term_parser(fields:fgetcsv($term_stream), parsedown:$c['parsedown'], log:$c['log'], natlang_etymologies:$natlang_etymologies);

        pard_sec("Parse entries");
        while(($data = fgetcsv($term_stream)) !== false) {

            // Parse term if it exists
            if (empty($data) || empty($data[0]) ) {
                continue;
            }
            
            [$raw, $parsed, $csv_row] = $tp->parse_term($data);

            $csv[$parsed['slug']] = $csv_row;
            $debug_data[$parsed['slug']] = $raw;
            if (isset($parsed['etymology'][')'])) unset($parsed['etymology'][')']);
            
            // DEBUG:
            if(!isset($parsed['category'])) {
                pard("Missing slug: ".$parsed['slug']);
            }
            if(!isset($parsed['category'])) {
                pard("Missing Category: ".$parsed['slug']);
            }
            // Insert entry in aggregate data
            self::insert_term_index(parsed:$parsed, index:$term_indexes);
            self::insert_search_terms(parsed:$parsed, index:$search_terms);
            self::insert_basic_entry(parsed:$parsed, raw:$raw, basic_entries:$basic_entries, config:$c);
            self::insert_minimum_entry(parsed:$parsed, min:$min_entries);
            self::insert_standard_entry($parsed);

            self::insert_tags(parsed:$parsed, tags:$tags);
            self::insert_examples($parsed);
            self::validate_and_count_category($c, $parsed['category'], $category_count, $parsed['term']);
            self::update_rhyme_data($parsed);

            $parsed_entries[$parsed['slug']] = $parsed;
            usleep(self::TINY_IO_DELAY);
        }
        if (!feof($term_stream)) {
            $c['log']->add("Unexpected fgetcsv() fail");
        }
        fclose($term_stream);
        
        pard_end();
        pard_sec("Post parse");
        // Insert data that needed all entries to be loaded
        pard("Referenced definition");
        self::insert_referenced_definition(entries:$parsed_entries, trans:$min_entries);
        pard("Derived terms");
        self::insert_derived_terms(derived_terms:$tp->backlinks, entries:$parsed_entries, config:$c);
        pard("Derived etymology");
        self::update_derived_etymology();
        pard("Rhymes");
        self::update_entry_rhymes($parsed_entries);
        pard_end();
        return $csv;
    }


    private static function insert_examples(array &$entry) {
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
     * For each languages, add that languages search terms to the
     * search term array for that languages.
     */
    private static function insert_search_terms(array $parsed, &$index ) {
        foreach ($parsed['search terms'] as $lang => $terms) {
            foreach ($terms as $term) {
                $index[$lang][$term][] = $parsed['slug'];
            }
        }
    }



    /**
     * For each languages add all term forms (the term and it's alt forms, if any)
     * to the index of all words.
     */
    private static function insert_term_index(array $parsed, array &$index) {
        $index[$parsed['slug']] = null;
        foreach($parsed['alt forms'] as $alt) {
            $index[$alt] = $parsed['slug'];
        }
    }

    /**
     * Renders minimum definitions for the current term
     * and adds them to the array of mini defs.
     */
    private static function insert_minimum_entry(array $parsed, array &$min) {
        foreach($parsed['trans html'] as $lang=>$trans) {
            $min[$lang][$parsed['slug']] = '<em>(' . $parsed['word class'] . ')</em> ' . $trans;
        }
    }



    /**
     * 
     */
    private static function insert_standard_entry(array $entry) {
        global $standard_entries;

        $standard_entries[$entry['slug']] = [
            'word class'=>$entry['word class'],
            'category'=>$entry['category'],
            'trans'=>$entry['trans'],
            'etymology'=>$entry['etymology']
        ];
    }



    /**
     * Renders tags for the current term
     * and adds them to the array of tags.
     */
    private static function insert_tags(array $parsed, array &$tags) {
        if (array_key_exists('tags', $parsed)) {
            foreach ($parsed['tags'] as $tag) {
                $tags[$tag][] = $parsed['slug'];
            }
        }
    }


    /**
     * Renders the basic entry for each language. Includes:
     *  term, class, category, translations.
     */
    private static function insert_basic_entry(array $parsed, array $raw, array &$basic_entries, array $config) {
        foreach($parsed['trans html'] as $lang=>$translation) {
            $basic_entries[$lang][$parsed['slug']] = array();
            $basic_entries[$lang][$parsed['slug']]['class'] = $parsed['word class'];
            $basic_entries[$lang][$parsed['slug']]['category'] = $parsed['category'];
            $basic_entries[$lang][$parsed['slug']]['translation'] = $translation;
        }
    }
    


    /**
     * Updates the etymology/derived field to include translations
     */
    private static function update_derived_etymology() {
        require_once('helpers/slugify.php');
        global $dict;

        foreach($dict as $slug=>$entry) {
            if (!isset($entry['etymology']['derived'])) continue;

            foreach($entry['etymology']['derived'] as $cur) {
                $cur = slugify($cur);

                if ((strlen($cur) === 1 && !ctype_alnum($cur)) || !isset($dict[$cur])) {
                    $dict[$slug]['etymology']['derived trans'][] = ['text'=>$cur];
                } else {
                    $dict[$slug]['etymology']['derived trans'][] = [
                        'text'=>$cur,
                        'word class'=>$dict[$cur]['word class'],
                        'trans'=>$dict[$cur]['trans html']
                    ];
                }
                
            }
        }
    }


    private static function update_entry_rhymes(array &$dict) {
        global $rhyme_data;

        $max = 0;
        $max_example = "";

        pard_progress_start(count($rhyme_data), "rhyme groups");
        // Go through each rhyme group to copy rhyming terms in to entry
        foreach($rhyme_data as $group) {
            if(count($group) < 2) {
                // skip if there are no rhymes
                continue;
            }
            if (count($group)>$max) {
                $max = count($group);
                $max_example = $group[array_key_first($group)];
            }

            foreach($group as $entry) {
                // For each entry
                foreach ($group as $rhyme) {
                    // Copy each rhyme
                    if ($group === $rhyme) continue;

                    $rhyme_slug = slugify($rhyme);

                    // Copy all data from this rhyme to the entry
                    $dict[$entry]['rhyme trans'][$rhyme_slug]['word class'] = $dict[$rhyme_slug]['word class'];

                    foreach($dict[$rhyme_slug]['trans html'] as $lang=>$trans) {
                        // Copy each translation, except for self

                        if ($entry===$rhyme_slug) continue;
                        $dict[$entry]['rhyme trans'][$rhyme_slug][$lang] = $trans;
                    }

                }

            }
            pard_progress_increment();
            usleep(500);
        }
        pard_progress_end();
        pard("Largest group: ".$max_example);
    }


    /**
     * Collecting rhyming data. A rhyme is the last two letters matching.
     * If there are no vowels, use 3.
     */
    private static function update_rhyme_data(array $entry):void {
        global $rhyme_data;

        if ($entry['category'] === 'phrase' || $entry['category'] === 'affix') {
            return;
        }

        $group = substr($entry['slug'], -2);

        if(!preg_match(GLOBAL_VOWEL_REGEX, $group)) {
            // If it does not have vowels use 3 letters
            $group = substr($entry['slug'], -3);
        }

        $rhyme_data[$group][] = $entry['slug'];
    }




    private static function validate_and_count(string $cat, array &$count_arr) {

        if (!isset($count_arr[$cat]))
            $count_arr[$cat] = 1;
        else
            $count_arr[$cat] += 1;

    }



    /**
     * Counts the category
     */
    static function validate_and_count_category(array $c, string $cat, &$count_arr, string $word) {

        global $parse_report;

        if (!in_array($cat, self::VALID_WORD_CATEGORIES)) {
            $c['log']->add("Word List Error: Invalid category `$cat` on term `$word`");
            $parse_report[] = ['term'=>$word, 'msg'=>"Word List Error: Invalid category `$cat`"];
        }

        self::validate_and_count($cat, $count_arr);
    }
}