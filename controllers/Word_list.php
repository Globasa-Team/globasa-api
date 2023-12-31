<?php
namespace globasa_api;
use Exception;
use Throwable;

class Word_list {

    // Microseconds (1 millions of a second)
    const TINY_IO_DELAY = 5000; // 5k microseconds = a twohundreds? of a second
    const SMALL_IO_DELAY = 50000; // 50k microseconds = a twentieth of a second
    const FULL_FILE_DELAY = 500000; // 500k microseconds = half second

    const VALID_WORD_CATEGORIES = array(
        'root', 'proper word', 'derived', 'phrase', 'affix'
    );



                
    /**
     * Count natlang sources.
     * 
     */
    private static function count_languages($parsed) {
        $lang_count = array();
        if (!empty($parsed['etymology']['natlang'])) {
            foreach($parsed['etymology']['natlang'] as $lang => $term_data) {
                if (!array_key_exists($lang, $lang_count)) $lang_count[$lang] = 1;
                else $lang_count[$lang] += 1;
            }
        }
        return $lang_count;
    }


    /**
     * Backlinks
     */
    public static function insert_backlinks(array &$entries, array &$backlinks, array &$trans) {
        foreach($backlinks as $backlink_term=>$terms) {
            // For each term, grab definitions for all languages
            foreach($terms as $slug) {
                foreach($trans as $lang=>$translations) {
                    if (isset($entries[$backlink_term]) && isset($trans[$lang][$slug])) {
                        $entries[$backlink_term]["also see"][$slug][$lang] = $trans[$lang][$slug];
                    } elseif (!isset($entries[$backlink_term])) {
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
            array &$lang_count,
            array &$category_count,
            
            array &$debug_data,
            array &$c, string $current_csv_filename
        ) {

        // Download the official term list, processing each term.
        $term_stream = fopen($current_csv_filename, "r")
            or throw new Exception("Failed to open ".$current_csv_filename);
        $tp = new Term_parser(fields:fgetcsv($term_stream), parsedown:$c['parsedown'], log:$c['log'], natlang_etymologies:$natlang_etymologies);

        while(($data = fgetcsv($term_stream)) !== false) {

            // Parse term if it exists
            if (empty($data) || empty($data[0]) ) {
                continue;
            }
            [$raw, $parsed, $csv_row] = $tp->parse_term($data);

            $csv[$parsed['slug']] = $csv_row;
            $debug_data[$parsed['slug']] = $raw;
            if (isset($parsed['etymology'][')'])) unset($parsed['etymology'][')']);
            
            self::render_term_index(parsed:$parsed, index:$term_indexes);
            self::render_search_terms(parsed:$parsed, index:$search_terms);
            self::render_basic_entry(parsed:$parsed, raw:$raw, basic_entries:$basic_entries, config:$c);
            self::render_minimum_translations(parsed:$parsed, min:$min_entries);
            self::render_tags(parsed:$parsed, tags:$tags);
            $lang_count = self::count_languages($parsed);
            self::validate_and_count_category($c, $parsed['category'], $category_count, $parsed['term']);
            
            $parsed_entries[$parsed['slug']] = $parsed;
            usleep(self::TINY_IO_DELAY);
        }
        if (!feof($term_stream)) {
            $c['log']->add("Unexpected fgetcsv() fail");
        }
        fclose($term_stream);


        // Insert data that needed for all entries to be loaded
        self::insert_referenced_definition(entries:$parsed_entries, trans:$min_entries);
        self::insert_backlinks(backlinks:$tp->backlinks, entries:$parsed_entries, trans:$min_entries);
        return $csv;
    }


    /**
     * For each languages, add that languages search terms to the
     * search term array for that languages.
     */
    private static function render_search_terms(array $parsed, &$index ) {
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
    private static function render_term_index(array $parsed, array &$index) {
        $index[$parsed['slug']] = null;
        foreach($parsed['alt forms'] as $alt) {
            $index[$alt] = $parsed['slug'];
        }
    }

    /**
     * Renders minimum definitions for the current term
     * and adds them to the array of mini defs.
     */
    private static function render_minimum_translations(array $parsed, array &$min) {
        foreach($parsed['trans html'] as $lang=>$trans) {
            $min[$lang][$parsed['slug']] = '<em>(' . $parsed['word class'] . ')</em> ' . $trans;
        }
    }

    /**
     * Renders tags for the current term
     * and adds them to the array of tags.
     */
    private static function render_tags(array $parsed, array &$tags) {
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
    private static function render_basic_entry(array $parsed, array $raw, array &$basic_entries, array $config) {
        foreach($parsed['trans html'] as $lang=>$translation) {
            $basic_entries[$lang][$parsed['slug']] = array();
            $basic_entries[$lang][$parsed['slug']]['class'] = $parsed['word class'];
            $basic_entries[$lang][$parsed['slug']]['category'] = $parsed['category'];
            $basic_entries[$lang][$parsed['slug']]['translation'] = $translation;
        }
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

        if (!in_array($cat, self::VALID_WORD_CATEGORIES)) {
            $c['log']->add("Word List Error: Invalid category `$cat` on term `$word`");
        }

        self::validate_and_count($cat, $count_arr);
    }
}