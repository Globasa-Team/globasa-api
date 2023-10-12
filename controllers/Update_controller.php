<?php
namespace globasa_api;
use Exception;
use Throwable;

class Update_controller {

    const IO_DELAY = 10000;

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

        // Download the official term list, processing each term.
        $term_stream = fopen($current_csv_filename, "r")
        or throw \Exception("Failed to open ".$current_csv_filename);
        $tp = new Term_parser(fgetcsv($term_stream), null, $c['log']);
        while(($data = fgetcsv($term_stream)) !== false) {
            if (empty($data) || empty($data[0])) {
                continue;
            }
            [$raw, $parsed, $csv_row] = $tp->parse($data);
            $csv[$parsed['slug']] = $csv_row;

            // Next: save entry to file
            $entry_file_data = $parsed;
            $entry_file_data['raw data'] = $raw;
            if (isset($parsed['etymology'][')'])) unset($parsed['etymology'][')']);
            yaml_emit_file($c['api_path'] . '/terms/' . $parsed['slug'].".yaml", $entry_file_data,  YAML_UTF8_ENCODING);
            
            // $index['eng'][$parsed['search terms']['eng']] = $parsed['slug'];
            foreach ($parsed['search terms'] as $lang => $terms) {
                foreach ($terms as $term) {
                    $index[$lang][$term][] = $parsed['slug'];
                }
            }

            // calc etymology
            if (!empty($parsed['etymology']['natlang'])) {
                foreach($parsed['etymology']['natlang'] as $lang => $term_data) {
                    if (!array_key_exists($lang, $lang_count)) $lang_count[$lang] = 1;
                    else $lang_count[$lang] += 1;
                }
            }

            // tag index
            if (array_key_exists('tags', $parsed)) {
                foreach ($parsed['tags'] as $tag) {
                    // if (array_key_exists($lang, $tags)) 
                    $tags[$tag][] = $parsed['slug'];
                }
            }

            // calc categories
            if (!isset($category_count[$parsed['category']]))
                $category_count[$parsed['category']] = 1;
            else
                $category_count[$parsed['category']] += 1;
            
            // Pause before next entry read and `yaml_file_emit`
            usleep(SELF::IO_DELAY);
        }
        if (!feof($term_stream)) {
            $c['log']->add("Unexpected fgetcsv() fail");
        }
        fclose($term_stream);

        //
        // Indexes
        //
        $index_list = "";
        foreach($index as $lang=>$data) {
            ksort($data);
            yaml_emit_file($c['api_path'] . "/index_{$lang}.yaml", $data);
            $index_list .= $lang . ' ';
            usleep(SELF::IO_DELAY);
        }
        $c['log']->add("Indexes created: " . $index_list);

        //
        // Tags
        //
        ksort($tags);
        yaml_emit_file($c['api_path'] . "/tags.yaml", $tags);
        $fp = fopen($c['api_path'] . "/tags.json", "w");
        fputs($fp, json_encode($tags));
        fclose($fp);
        usleep(SELF::IO_DELAY);

        //
        // Statistics
        //
        array_multisort($lang_count);
        yaml_emit_file($c['api_path'] . "/stats.yaml", ["source langs"=> $lang_count, "category count"=>$category_count]);

        return $csv;
    }


}