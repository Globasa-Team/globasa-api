<?php
namespace globasa_api;
use Exception;
use Throwable;

class Update_controller {

    // Microseconds (1 millions of a second)
    const SMALL_IO_DELAY = 50000; // 50k microseconds = a twentieth of a second
    const FULL_FILE_DELAY = 500000; // 500k microseconds = half second

    const VALID_WORD_CATEGORIES = array(
        'root', 'proper noun', 'derived', 'phrase', 'affix'
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
     * Compare the new and old word list and log any changes.
     */
    static function log_changes(array $current_data, string $old_csv_filename, array $c) {
        $old_data = loadCsv($old_csv_filename);
        
        // Find changes
        $comparison = new Dictionary_comparison($old_data, $current_data, $c);
        // Log changes
        $log = new Dictionary_log($c);
        $log->add($comparison->changes);
        $c['log']->add("Changes logged: ".count($comparison->changes));
    }



    public static function update_i18n(array $c) {
        $lang_resource = [];
        $lang_resource_csv = fopen($c['api_path'] . DIRECTORY_SEPARATOR . I18N_CSV_FILENAME, 'r');
        // $lang_resource_csv = fopen($c['i18n_url'], 'r');
        if ($lang_resource_csv === false) {
            die("Failed to open lang CSV");
        }
        //What does this do on failure? Empty file? No file found?

        $columnNames = fgetcsv($lang_resource_csv);
        $label_id = ''; // Should be set on first loop
        while (($text_data = fgetcsv($lang_resource_csv)) !== false) {
            foreach ($text_data as $key=>$datum) {
                // Key is label id when in first position.
                if ($key == 0) {
                    $label_id = $datum;
                    continue;
                }
                // key is language otherwise.
                $lang_resource[$columnNames[$key]][$label_id] = $datum;
            }
        }
        yaml_emit_file($c['api_path'] . DIRECTORY_SEPARATOR . I18N_YAML_FILENAME, $lang_resource);
    }


    /**
     * Open current CSV, reading line by line, and processing the words
     * individually and writing out dictionary files. This is to reduce max
     * load on the server. A usleep() delay between each term is used.
     */
    public static function load_current_terms(array $c, string $current_csv_filename) {
        $search_terms = [];
        $term_indexes = [];
        $lang_count = [];
        $category_count = [];
        $tags = [];
        $min = [];
        $basic_entries = [];
        $word_count = 0;

        // Download the official term list, processing each term.
        $term_stream = fopen($current_csv_filename, "r")
            or throw new Exception("Failed to open ".$current_csv_filename);
        $tp = new Term_parser(fgetcsv($term_stream), $c['parsedown'], $c['log']);

        while(($data = fgetcsv($term_stream)) !== false) {

            // Parse term if it exists
            if (empty($data) || empty($data[0])) {
                continue;
            }
            [$raw, $parsed, $csv_row] = $tp->parse_term($data);
            $csv[$parsed['slug']] = $csv_row;
            if (isset($parsed['etymology'][')'])) unset($parsed['etymology'][')']);

            self::save_entry_file(parsed:$parsed, raw:$raw, config:$c);
            self::render_term_index(parsed:$parsed, index:$term_indexes);
            self::render_search_terms(parsed:$parsed, index:$search_terms);
            self::render_basic_entry(parsed:$parsed, raw:$raw, basic_entries:$basic_entries);
            self::render_minimum_definitions(parsed:$parsed, raw:$raw, min:$min, config:$c);
            self::render_tags(parsed:$parsed, tags:$tags);
            $lang_count = self::count_languages($parsed);
            self::validate_and_count_category($c, $parsed['category'], $category_count, $parsed['term']);

            $word_count += 1;
        }
        if (!feof($term_stream)) {
            $c['log']->add("Unexpected fgetcsv() fail");
        }
        fclose($term_stream);

        self::save_search_term_files(index:$search_terms, config:$c);
        self::save_min_files(min:$min, config:$c);
        self::save_term_index_file(data:$term_indexes, config:$c);
        self::save_basic_files(data:$basic_entries, config:$c);
        self::save_tag_file(tags:$tags, config:$c);
        self::save_stats_file(word_count:$word_count, lang_count:$lang_count, category_count:$category_count, config:$c);

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
    private static function render_minimum_definitions(array $parsed, array $raw, array &$min, array $config) {
        foreach($raw['trans'] as $lang=>$trans) {
            $min[$lang][$parsed['slug']] = $config['parsedown']->line('(_' . $raw['word class'] . '_) ' . $trans);
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
    private static function render_basic_entry(array $parsed, array $raw, array &$basic_entries) {
        foreach($raw['trans'] as $lang=>$translation) {
            $basic_entries[$lang][$parsed['slug']] = array();
            $basic_entries[$lang][$parsed['slug']]['class'] = $parsed['word class'];
            $basic_entries[$lang][$parsed['slug']]['category'] = $parsed['category'];
            $basic_entries[$lang][$parsed['slug']]['translation'] = $translation;
        }
    }
    



    private static function save_basic_files(array $data, array $config) {
        foreach($data as $lang=>$dict) {
            ksort($dict);

            yaml_emit_file($config['api_path'] . "/basic_{$lang}.yaml", $dict);
            usleep(SELF::FULL_FILE_DELAY);

            $fp = fopen($config['api_path'] . "/basic_{$lang}.json", "w");
            fputs($fp, json_encode($dict));
            fclose($fp);
            usleep(SELF::FULL_FILE_DELAY);
        }
    }

    private static function save_entry_file(array $config, array $parsed, array $raw) {
        $entry_file_data = $parsed;
        $entry_file_data['raw data'] = $raw;

        yaml_emit_file($config['api_path'] . '/terms/' . $parsed['slug'].".yaml", $entry_file_data,  YAML_UTF8_ENCODING);
        usleep(SELF::SMALL_IO_DELAY);

        $fp = fopen($config['api_path'] . '/terms/' . $parsed['slug'].".json", "w");
        fputs($fp, json_encode($entry_file_data));
        fclose($fp);
        usleep(SELF::SMALL_IO_DELAY);

        return;
    }



    /**
    * Indexes
    */
    private static function save_search_term_files(array $index, array $config) {

        $index_list = "";
        foreach($index as $lang=>$data) {
            ksort($data);

            yaml_emit_file($config['api_path'] . "/search_terms_{$lang}.yaml", $data);
            $index_list .= $lang . ' ';
            usleep(SELF::FULL_FILE_DELAY);

            $fp = fopen($config['api_path'] . "/search_terms_{$lang}.json", "w");
            fputs($fp, json_encode($data));
            fclose($fp);
            usleep(SELF::FULL_FILE_DELAY);
        }
        $config['log']->add("Search term indexes created: " . $index_list);
    }





    /**
     * Min
     */
    private static function save_min_files(array $config, array $min) {

        $min_list = "";
        foreach ($min as $lang=>$data) {
            ksort($data);

            yaml_emit_file($config['api_path'] . "/min_{$lang}.yaml", $data);
            $min_list .= $lang . ' ';
            usleep(SELF::FULL_FILE_DELAY);

            $fp = fopen($config['api_path'] . "/min_{$lang}.json", "w");
            fputs($fp, json_encode($data));
            fclose($fp);
            usleep(SELF::FULL_FILE_DELAY);
            
        }
        $config['log']->add("Minimum translation files created: " . $min_list);
    }






    /**
     * Statistics
     * 
     */
    private static function save_stats_file(int $word_count, array $lang_count, array $category_count, array $config) {

        array_multisort($lang_count, SORT_DESC);
        yaml_emit_file($config['api_path'] . "/stats.yaml", [
                        "terms count"=>$word_count,
                        "source langs"=>$lang_count,
                        "categories"=>$category_count
                    ]);
        usleep(SELF::SMALL_IO_DELAY);

    }


                

    /**
     * Tags
     */
    private static function save_tag_file(array $config, array $tags) {
        ksort($tags);

        yaml_emit_file($config['api_path'] . "/tags.yaml", $tags);
        usleep(SELF::FULL_FILE_DELAY);

        $fp = fopen($config['api_path'] . "/tags.json", "w");
        fputs($fp, json_encode($tags));
        fclose($fp);

        usleep(SELF::FULL_FILE_DELAY);
    }


    private static function save_term_index_file(array $data, array $config) {
        ksort($data);

        yaml_emit_file($config['api_path'] . "/index.yaml", $data);
        usleep(SELF::FULL_FILE_DELAY);

        $fp = fopen($config['api_path'] . "/index.json", "w");
        fputs($fp, json_encode($data));
        fclose($fp);

        usleep(SELF::FULL_FILE_DELAY);       
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
            $c['log']->add("Word List Error: Invalid ctegory `$cat` on term `$word`");
        }

        Update_controller::validate_and_count($cat, $count_arr);
    }
}