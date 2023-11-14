<?php
namespace globasa_api;
use Exception;
use Throwable;

class I18n {

    // Microseconds (1 millions of a second)
    const SMALL_IO_DELAY = 50000; // 50k microseconds = a twentieth of a second
    const FULL_FILE_DELAY = 500000; // 500k microseconds = half second

    
    public static function update(array $c) {
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

}