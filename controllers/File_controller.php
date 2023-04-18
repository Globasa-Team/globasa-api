<?php
namespace globasa_api;

class File_controller {

    public static function create_api2_files($term_array, $changeLog) {
        $all_terms = [];
        $all_terms_with_raw = [];
        $term_index = [];

        foreach($term_array as $cur) {
            [$slug, $term_data_with_raw[$slug], $term_data[$slug]] = Term::parse_term_array($cur);

            if(isset($term_index[$slug])) {
                // Log error regarding the term slug collision.
                continue;
            }

            // Only save data out if it's changed... But how do I know that? Move comparison in to here? That makes sense, actually.
            // Since I cannot email out until this is done anyway.
        }

    }
}