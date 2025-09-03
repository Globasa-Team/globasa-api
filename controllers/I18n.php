<?php

namespace WorldlangDict\API;

use Exception;
use Throwable;

class I18n
{

    public static function update()
    {
        global $cfg;

        $lang_resource = [];
        $lang_resource_csv = fopen($cfg['api_path'] . DIRECTORY_SEPARATOR . I18N_CSV_FILENAME, 'r');
        // $lang_resource_csv = fopen($c['i18n_url'], 'r');
        if ($lang_resource_csv === false) {
            die("Failed to open lang CSV");
        }
        //What does this do on failure? Empty file? No file found?

        $columnNames = fgetcsv($lang_resource_csv, escape: "");
        $label_id = ''; // Should be set on first loop
        while (($text_data = fgetcsv($lang_resource_csv, escape: "")) !== false) {
            foreach ($text_data as $key => $datum) {
                // Key is label id when in first position.
                if ($key == 0) {
                    $label_id = $datum;
                    continue;
                }
                // key is language otherwise.
                $lang_resource[$columnNames[$key]][$label_id] = $datum;
            }
        }
        yaml_emit_file($cfg['api_path'] . DIRECTORY_SEPARATOR . I18N_YAML_FILENAME, $lang_resource);
    }
}
