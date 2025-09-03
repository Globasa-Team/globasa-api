<?php

namespace WorldlangDict\API;

class Dictionary_comparison
{

    private $c;
    private $added_terms, $same_terms, $removed_terms, $renamed_terms;
    public $changes;

    public function __construct()
    {
        global $cfg;
        $this->c = $cfg;
        $this->changes = [];
        $this->compare_dictionaries();
    }

    function compare_dictionaries()
    {
        global $old_csv_data, $new_csv_data;

        $old_terms = array_keys($old_csv_data);
        $new_terms = array_keys($new_csv_data);

        $this->same_terms = array_intersect($old_terms, $new_terms);
        $this->removed_terms = array_diff($old_terms, $new_terms);
        $this->added_terms = array_diff($new_terms, $old_terms);

        // look for renamed terms, & log.
        foreach ($this->added_terms as $added_key => $added_term) {
            foreach ($this->removed_terms as $removed_key => $removed_term) {
                foreach ($this->c['translated_languages'] as $lang) {
                    // If this has any translation field the same, log as a renamed term
                    if (!empty($old_csv_data[$removed_term][$lang]) && ($old_csv_data[$removed_term][$lang] === $new_csv_data[$added_term][$lang])) {
                        // Save log to both terms
                        $this->log_term_change($removed_term, "term renamed", "", $removed_term, $added_term);
                        $this->log_term_change($added_term, "term renamed", "", $removed_term, $added_term);
                        // Log other changes
                        $this->compare_terms($removed_term, $added_term);
                        // Remove from other indexes
                        unset($this->removed_terms[$removed_key]);
                        unset($this->added_terms[$added_key]);
                        break 2;
                    }
                }
            }
        }

        // Log added terms
        foreach ($this->added_terms as $term) {
            $this->log_term_change($term, "term added", "", null, $new_csv_data[$term]);
        }
        // Log removed terms
        $i = 0;
        foreach ($this->removed_terms as $term) {
            if (isset($this->c['dev']) && $this->c['dev'] && $i++ % 200 == 0 && $i != 1) {
                echo ("\n\n\n\n*\tpress enter\n\n");
                readline();
            }
            $this->log_term_change($term, "term removed", "", $old_csv_data[$term], null);
        }
        // Compare same terms for changes, & log
        foreach ($this->same_terms as $term) {
            $this->compare_terms($term);
        }
    }

    function compare_terms($term1, $term2 = null)
    {
        global $old_csv_data, $new_csv_data;

        if ($term2 == null) $term2 = $term1;

        $term1 = trim($term1);
        $term2 = trim($term2);

        // Compare new and old for each term
        if (array_key_exists($term1, $old_csv_data) && array_key_exists($term2, $new_csv_data)) {
            foreach ($old_csv_data[$term1] as $field => $datum) {
                // Find changed fields for this term
                if (array_key_exists($field, $new_csv_data[$term2]) && strcmp($old_csv_data[$term1][$field], $new_csv_data[$term2][$field]) != 0 && $field !== 'Word') {
                    $this->log_term_change($term2, "field updated", $field, $old_csv_data[$term1][$field], $new_csv_data[$term2][$field]);
                }

                // Find missing fields for each term
                else if (!array_key_exists($field, $new_csv_data[$term2])) {
                    $this->log_term_change($term1, "field removed", $field, $old_csv_data[$term1][$field], null);
                }
            }

            // Find new fields for this term
            $example_old_entry = $old_csv_data[array_key_first($old_csv_data)];
            $example_new_entry = $new_csv_data[array_key_first($new_csv_data)];
            $new_keys = array_diff_key($example_new_entry, $example_old_entry);

            foreach ($new_keys as $field => $value) {

                if (empty($value)) {
                    $this->log_term_change($term2, "field added", $field, null, $new_csv_data[$term2][$field]);
                }
            }
        }
    }

    function log_term_change($term, $type, $field, $old_data, $new_data)
    {
        $log = [];
        $log['type'] = $type;
        $log['term'] = $term;
        $log['field'] = $field;
        $log['message'] = match ($type) {

            'field updated' =>
            "&lsquo;" . $field . "&rsquo; updated from &lsquo;" .
                "{$old_data}&rsquo; to &lsquo;{$new_data}&rsquo;",

            'field removed' =>
            "&lsquo;{$field}&rsquo; field removed, contained &lsquo;" .
                "{$old_data}&rsquo;",

            'field added' =>
            "&lsquo;{$field}&rsquo; field added with value " .
                "&lsquo;{$new_data}&rsquo;",

            'field renamed' => "&lsquo;" . $field . "&rsquo; field renamed to {$new_data}",

            'term removed' =>
            "Old term removed: " . implode(',', $old_data),

            'term added' =>
            "New term added: " . implode(',', $new_data),

            'term renamed' =>
            "Term renamed from &lsquo;{$old_data}&rsquo; to &lsquo;{$new_data}&rsquo;",
        };
        $this->changes[] = $log;
    }
}
