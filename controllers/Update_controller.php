<?php
namespace globasa_api;
use Exception;
use Throwable;

class Update_controller {

    const IO_DELAY = 10000;

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
        $index = [];
        $lang_count = [];
        $category_count = [];
        $tags = [];
        $min = [];
        $word_count = 0;

        // Download the official term list, processing each term.
        $term_stream = fopen($current_csv_filename, "r")
            or throw \Exception("Failed to open ".$current_csv_filename);
        $tp = new Term_parser(fgetcsv($term_stream), null, $c['log']);

        while(($data = fgetcsv($term_stream)) !== false) {

            // Parse term if it exists
            if (empty($data) || empty($data[0])) {
                continue;
            }
            [$raw, $parsed, $csv_row] = $tp->parse_term($data);
            $csv[$parsed['slug']] = $csv_row;
            if (isset($parsed['etymology'][')'])) unset($parsed['etymology'][')']);

            self::save_entry_file(parsed:$parsed, raw:$raw, config:$c);
            self::render_indexes(parsed:$parsed, index:$index);
            self::render_minimum_definitions(parsed:$parsed, raw:$raw, min:$min, config:$c);
            self::render_tags(parsed:$parsed, tags:$tags);
            $lang_count = self::count_languages($parsed);
            self::validate_and_count_category($c, $parsed['category'], $category_count, $parsed['term']);

            $word_count += 1;

            // Pause before next entry read and `yaml_file_emit`
            usleep(SELF::IO_DELAY);
        }
        if (!feof($term_stream)) {
            $c['log']->add("Unexpected fgetcsv() fail");
        }
        fclose($term_stream);

        self::save_index_files(index:$index, config:$c);
        self::save_min_files(min:$min, config:$c);
        self::save_tag_file(tags:$tags, config:$c);
        self::save_stats_file(word_count:$word_count, lang_count:$lang_count, category_count:$category_count, config:$c);

        return $csv;
    }


    /**
     * Renders indexes for this term and adds them to the array of indexes.
     */
    private static function render_indexes(array $parsed, &$index ) {
        foreach ($parsed['search terms'] as $lang => $terms) {
            foreach ($terms as $term) {
                $index[$lang][$term][] = $parsed['slug'];
            }
        }
    }

    /**
     * Renders minimum definitions and adds them to the array of mini defs.
     */
    private static function render_minimum_definitions(array $parsed, array $raw, array &$min, array $config) {
        foreach($parsed['minimum definitions'] as $term) {
            foreach($raw['trans'] as $lang=>$trans) {
                $min[$lang][$term] = $config['parsedown']->line('(_' . $raw['word class'] . '_) ' . $trans);
            }
        }
    }

    /**
     * Renders tags and adds them to the array of tags.
     */
    private static function render_tags(array $parsed, array &$tags) {
        if (array_key_exists('tags', $parsed)) {
            foreach ($parsed['tags'] as $tag) {
                $tags[$tag][] = $parsed['slug'];
            }
        }
    }
    


    private static function save_entry_file(array $config, array $parsed, array $raw) {
        $entry_file_data = $parsed;
        $entry_file_data['raw data'] = $raw;
        yaml_emit_file($config['api_path'] . '/terms/' . $parsed['slug'].".yaml", $entry_file_data,  YAML_UTF8_ENCODING);

    }



    /**
    * Indexes
    */
    private static function save_index_files(array $index, array $config) {

        $index_list = "";
        foreach($index as $lang=>$data) {
            ksort($data);
            yaml_emit_file($config['api_path'] . "/index_{$lang}.yaml", $data);
            $index_list .= $lang . ' ';
            usleep(SELF::IO_DELAY);
        }
        $config['log']->add("Indexes created: " . $index_list);
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
            usleep(SELF::IO_DELAY);
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
    }


                

    /**
     * Tags
     */
    private static function save_tag_file(array $config, array $tags) {

        ksort($tags);
        yaml_emit_file($config['api_path'] . "/tags.yaml", $tags);
        $fp = fopen($config['api_path'] . "/tags.json", "w");
        fputs($fp, json_encode($tags));
        fclose($fp);
        usleep(SELF::IO_DELAY);
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