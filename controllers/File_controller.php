<?php
namespace globasa_api;

class File_controller {

    public static function write_api2_files() {

        global $import_report, $rhyme_data, $dev_report;
        pard_sec("Writing API files");

        self::save_entry_files();
        self::save_search_term_files();
        self::save_min_files();
        self::save_standard_files();
        self::save_term_index_file();
        self::save_basic_files();
        self::save_tag_file();
        self::save_natlang_etymologies_files();

        self::save_stats_file();

        self::save_report(data:$import_report, name:"import_report");
        self::save_report(data:$rhyme_data, name:"rhyme_report");
        self::save_report(data:$dev_report, name:"dev_report");

        pard_end();
    }

    
    private static function save_basic_files() {
        global $cfg, $basic_entries;

        pard("save_basic_files");

        foreach($basic_entries as $lang=>$dict) {
            ksort($dict);

            yaml_emit_file($cfg['api_path'] . "/basic_{$lang}.yaml", $dict, YAML_UTF8_ENCODING);
            usleep(FULL_FILE_DELAY);

            $fp = fopen($cfg['api_path'] . "/basic_{$lang}.json", "w");
            fputs($fp, json_encode($dict));
            fclose($fp);
            usleep(FULL_FILE_DELAY);
        }
    }



    private static function save_entry_files() {
        global $cfg, $dict;

        $first = "";
        pard_step_start("Saving entry files");
        foreach($dict as $key=>$entry) {
            if ($entry['slug'][0] !== $first) {

                if(!isset($entry['slug'])) {
                    $cfg['log']->add(" - Entry key '{$key}' missing slug", 6);
                    continue;
                }

                $first = $entry['slug'][0];
                pard_step($first);
            }
            
            self::save_entry_file($entry);
        }
        pard_step_end();
    }

    private static function save_entry_file(array &$parsed) {
        global $cfg;
        $entry_file_data = $parsed;

        yaml_emit_file($cfg['api_path'] . '/terms/' . $parsed['slug'].".yaml", $entry_file_data,  YAML_UTF8_ENCODING);
        usleep(SMALL_IO_DELAY);

        $fp = fopen($cfg['api_path'] . '/terms/' . $parsed['slug'].".json", "w");
        fputs($fp, json_encode($entry_file_data));
        fclose($fp);
        usleep(SMALL_IO_DELAY);

        return;
    }



    /**
     * Standard entry dictionary file
     */
    private static function save_standard_files() {
        global $cfg, $standard_entries;
        $cfg['log']->add("save_standard_files ", 5);

        yaml_emit_file($cfg['api_path'] . '/standard.yaml', $standard_entries,  YAML_UTF8_ENCODING);
        usleep(FULL_FILE_DELAY);

        file_put_contents($cfg['api_path'] . '/standard.json', json_encode($standard_entries));
        usleep(FULL_FILE_DELAY);
    }

    /**
    * Indexes
    */
    private static function save_search_term_files() {
        global $cfg, $search_terms;

        pard("save_search_term_files ");

        foreach($search_terms as $lang=>$index) {
            ksort($index);

            yaml_emit_file($cfg['api_path'] . "/search_terms_{$lang}.yaml", $index, YAML_UTF8_ENCODING);
            usleep(FULL_FILE_DELAY);

            $fp = fopen($cfg['api_path'] . "/search_terms_{$lang}.json", "w");
            fputs($fp, json_encode($index));
            fclose($fp);
            usleep(FULL_FILE_DELAY);
        }
    }





    /**
     * Min
     */
    private static function save_min_files() {
        global $cfg, $min_entries;
        pard("save_min_files ");

        foreach ($min_entries as $lang=>$data) {
            ksort($data);

            yaml_emit_file($cfg['api_path'] . "/min_{$lang}.yaml", $data, YAML_UTF8_ENCODING);
            usleep(FULL_FILE_DELAY);

            $fp = fopen($cfg['api_path'] . "/min_{$lang}.json", "w");
            fputs($fp, json_encode($data));
            fclose($fp);
            usleep(FULL_FILE_DELAY);
        }
    }



    /**
     * Natlang etymologies
     */
    private static function save_natlang_etymologies_files() {
        global $cfg, $natlang_etymologies;
        pard("save_natlang_etymologies_files ");

        foreach ($natlang_etymologies as $lang=>$terms) {
            ksort($terms);

            yaml_emit_file($cfg['api_path'] . "/etymologies/etymology_".strtolower($lang).".yaml", $terms, YAML_UTF8_ENCODING);
            usleep(FULL_FILE_DELAY);
        }
    }



    /**
     * Save report
     */
    private static function save_report(array $data, string $name) {
        global $cfg;
        pard("Saving report: ".$name);

        yaml_emit_file($cfg['api_path'] . "/reports/{$name}.yaml", $data, YAML_UTF8_ENCODING);

        usleep(FULL_FILE_DELAY);

        $fp = fopen($cfg['api_path'] . "/reports/{$name}.json", "w");
        fputs($fp, json_encode($data));
        fclose($fp);

        usleep(FULL_FILE_DELAY);
    }


    /**
     * Statistics
     * 
     */
    private static function save_stats_file() {
        global $cfg, $dict, $stats, $category_count, $natlang_etymologies, $cfg;

        pard("save_stats_file");
        $natlang_count = [];
        /**
         * Calculate natlang etymology counts
         */
        foreach(array_keys($natlang_etymologies) as $natlang) {
            $natlang_count[$natlang] = count($natlang_etymologies[$natlang]);
        }
        arsort($natlang_count);
        
        $stats["terms count"] = count($dict);
        $stats["source langs"] = $natlang_count;
        $stats["categories"] = $category_count;

        yaml_emit_file($cfg['api_path'] . "/stats.yaml", $stats, YAML_UTF8_ENCODING);
        usleep(SMALL_IO_DELAY);

    }




    /**
     * Tags
     */
    private static function save_tag_file() {
        global $cfg, $tags;
        pard("save_tag_file");

        ksort($tags);

        yaml_emit_file($cfg['api_path'] . "/tags.yaml", $tags, YAML_UTF8_ENCODING);

        usleep(FULL_FILE_DELAY);

        $fp = fopen($cfg['api_path'] . "/tags.json", "w");
        fputs($fp, json_encode($tags));
        fclose($fp);

        usleep(FULL_FILE_DELAY);
    }


    private static function save_term_index_file() {
        global $cfg, $term_index;
        pard("save_term_index_file");
        
        ksort($term_index);

        yaml_emit_file($cfg['api_path'] . "/index.yaml", $term_index, YAML_UTF8_ENCODING);
        usleep(FULL_FILE_DELAY);

        $fp = fopen($cfg['api_path'] . "/index.json", "w");
        fputs($fp, json_encode($term_index));
        fclose($fp);

        usleep(FULL_FILE_DELAY);       
    }



}