<?php
namespace globasa_api;

class File_controller {

    public static function write_api2_files(
        array &$parsed_entries,

        array &$min_entries,
        array &$basic_entries,
        
        array &$term_indexes,
        array &$search_terms,
        array &$tags,
        
        array &$natlang_etymologies,
        
        int &$word_count,
        array &$category_count,
        
        array &$config
    ) {

        global $parse_report;

        $config['log']->add("save_entry_files ", 5);
        self::save_entry_files      (data:$parsed_entries,  config:$config);
        $config['log']->add("save_search_term_files ", 5);
        self::save_search_term_files(data:$search_terms,    config:$config);
        $config['log']->add("save_min_files ", 5);
        self::save_min_files        (data:$min_entries,     config:$config);
        $config['log']->add("save_term_index_file ", 5);
        self::save_term_index_file  (data:$term_indexes,    config:$config);
        $config['log']->add("save_basic_files ", 5);
        self::save_basic_files      (data:$basic_entries,   config:$config);
        $config['log']->add("save_tag_file ", 5);
        self::save_tag_file         (data:$tags,            config:$config);
        $config['log']->add("save_natlang_etymologies_files ", 5);
        self::save_natlang_etymologies_files(data:$natlang_etymologies, config:$config);

        $config['log']->add("save_stats_file ", 5);
        self::save_stats_file       (word_count:$word_count, category_count:$category_count, natlang_data:$natlang_etymologies, config:$config);

        $config['log']->add("save_report_file", 5);
        self::save_report(config: $config, data:$parse_report, name:"parse_report");


    }

    
    private static function save_basic_files(array &$data, array &$config) {
        foreach($data as $lang=>$dict) {
            ksort($dict);

            yaml_emit_file($config['api_path'] . "/basic_{$lang}.yaml", $dict);
            usleep(FULL_FILE_DELAY);

            $fp = fopen($config['api_path'] . "/basic_{$lang}.json", "w");
            fputs($fp, json_encode($dict));
            fclose($fp);
            usleep(FULL_FILE_DELAY);
        }
    }

    private static function save_entry_files(array &$config, array &$data) {
        $first = "~";
        foreach($data as $key=>$entry) {
            if ($entry['slug'][0] !== $first) {

                if(!isset($entry['slug'])) {
                    $config['log']->add(" - Entry key '{$key}' missing slug", 6);
                    continue;
                }

                $first = $entry['slug'][0];
                $config['log']->add("Saving entries starting with ".$first, 9);
            }
            
            self::save_entry_file($config, $entry);
        }
    }

    private static function save_entry_file(array &$config, array &$parsed) {
        $entry_file_data = $parsed;

        yaml_emit_file($config['api_path'] . '/terms/' . $parsed['slug'].".yaml", $entry_file_data,  YAML_UTF8_ENCODING);
        usleep(SMALL_IO_DELAY);

        $fp = fopen($config['api_path'] . '/terms/' . $parsed['slug'].".json", "w");
        fputs($fp, json_encode($entry_file_data));
        fclose($fp);
        usleep(SMALL_IO_DELAY);

        return;
    }



    /**
    * Indexes
    */
    private static function save_search_term_files(array &$data, array &$config) {

        $index_list = "";
        foreach($data as $lang=>$index) {
            ksort($index);

            yaml_emit_file($config['api_path'] . "/search_terms_{$lang}.yaml", $index);
            $index_list .= $lang . ' ';
            usleep(FULL_FILE_DELAY);

            $fp = fopen($config['api_path'] . "/search_terms_{$lang}.json", "w");
            fputs($fp, json_encode($index));
            fclose($fp);
            usleep(FULL_FILE_DELAY);
        }
        $config['log']->add("Search term indexes created: " . $index_list);
    }





    /**
     * Min
     */
    private static function save_min_files(array &$config, array &$data) {

        $min_list = "";
        foreach ($data as $lang=>$data) {
            ksort($data);

            yaml_emit_file($config['api_path'] . "/min_{$lang}.yaml", $data);
            $min_list .= $lang . ' ';
            usleep(FULL_FILE_DELAY);

            $fp = fopen($config['api_path'] . "/min_{$lang}.json", "w");
            fputs($fp, json_encode($data));
            fclose($fp);
            usleep(FULL_FILE_DELAY);
            
        }
        $config['log']->add("Minimum translation files created: " . $min_list);
    }



    /**
     * Natlang etymologies
     */
    private static function save_natlang_etymologies_files(array &$config, array &$data) {

        foreach ($data as $lang=>$terms) {
            ksort($terms);

            yaml_emit_file($config['api_path'] . "/etymologies_".strtolower($lang).".yaml", $terms);
            usleep(FULL_FILE_DELAY);
        }
    }



    /**
     * Save report
     */
    private static function save_report(array $config, array $data, string $name) {

        yaml_emit_file($config['api_path'] . "/reports/{$name}.yaml", $data);

        usleep(FULL_FILE_DELAY);

        $fp = fopen($config['api_path'] . "/reports/{$name}.json", "w");
        fputs($fp, json_encode($data));
        fclose($fp);

        usleep(FULL_FILE_DELAY);
    }


    /**
     * Statistics
     * 
     */
    private static function save_stats_file(int &$word_count, array &$category_count, array &$natlang_data, array &$config) {

        $natlang_count = [];
        /**
         * Calculate natlang etymology counts
         */
        foreach(array_keys($natlang_data) as $natlang) {
            $natlang_count[$natlang] = count($natlang_data[$natlang]);
        }



        // array_multisort($natlang_count, SORT_DESC);
        yaml_emit_file($config['api_path'] . "/stats.yaml", [
                        "terms count"=>$word_count,
                        "source langs"=>$natlang_count,
                        "categories"=>$category_count
                    ]);
        usleep(SMALL_IO_DELAY);

    }


                

    /**
     * Tags
     */
    private static function save_tag_file(array &$config, array &$data) {
        ksort($data);

        yaml_emit_file($config['api_path'] . "/tags.yaml", $data);

        usleep(FULL_FILE_DELAY);

        $fp = fopen($config['api_path'] . "/tags.json", "w");
        fputs($fp, json_encode($data));
        fclose($fp);

        usleep(FULL_FILE_DELAY);
    }


    private static function save_term_index_file(array &$data, array &$config) {
        ksort($data);

        yaml_emit_file($config['api_path'] . "/index.yaml", $data);
        usleep(FULL_FILE_DELAY);

        $fp = fopen($config['api_path'] . "/index.json", "w");
        fputs($fp, json_encode($data));
        fclose($fp);

        usleep(FULL_FILE_DELAY);       
    }



}