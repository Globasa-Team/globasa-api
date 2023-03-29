<?php
namespace globasa_api;

class DictionaryComparison {

    private $c;
    private $new_dict, $old_dict;
    private $added_terms, $same_terms, $removed_terms, $renamed_terms;
    public $changes;

    public function __construct($old, $new, $config)
    {
        $this->c = $config;
        $this->old_dict = $old;
        $this->new_dict = $new;
        $this->compare_dictionaries();
    }

    function compare_dictionaries() {
        $old_terms = array_keys($this->old_dict);
        $new_terms = array_keys($this->new_dict);

        $this->same_terms = array_intersect($old_terms, $new_terms);
        $this->removed_terms = array_diff($old_terms, $new_terms);
        $this->added_terms = array_diff($new_terms, $old_terms);

        // look for renamed terms, & log.
        foreach($this->added_terms as $new_key => $new_term) {
            foreach($this->removed_terms as $old_key => $old_term) {
                foreach($this->c['translated_languages'] as $lang) {
                    // If this has any translation field the same, log as a renamed term
                    if (strcmp($this->old_dict[$old_term][$lang], $this->new_dict[$new_term][$lang]) == 0) {
                        // Save log to both terms
                        $this->log_term_change($old_term, "term renamed", "", $old_term, $new_term);
                        $this->log_term_change($new_term, "term renamed", "", $old_term, $new_term);
                        // Log other changes
                        $this->compare_terms($old_term, $new_term);
                        // Remove from other indexes
                        unset($this->removed_terms[$old_key]);
                        unset($this->added_terms[$new_key]);
                        break 2;
                    }
                }
            }
        }

        // Log added terms
        foreach($this->added_terms as $term) {
            $this->log_term_change($term, "term added", "", null, $this->new_dict);
        }
        // Log removed terms
        foreach($this->removed_terms as $term) {
            $this->log_term_change($term, "term removed", "", $this->old_dict, null);
        }
        // Compare same terms for changes, & log
        foreach($this->same_terms as $term) {
            $this->compare_terms($term);
        }
    }

    function compare_terms($term1, $term2 = null) {

        if ($term2 == null) $term2 = $term1;
        // Compare new and old for each term
        if (array_key_exists($term1, $this->old_dict) && array_key_exists($term2, $this->new_dict)) {
            foreach($this->old_dict[$term1] as $field=>$datum) {
                // Find changed fields for this term
                if (array_key_exists($field, $this->new_dict[$term2]) && strcmp($this->old_dict[$term1][$field], $this->new_dict[$term2][$field]) != 0) {
                    $this->log_term_change($term2, "field updated", $field, $this->old_dict[$term1][$field], $this->new_dict[$term2][$field]);
                }

                // Find missing fields for each term
                else if (!array_key_exists($field, $this->new_dict[$term2])) {
                    $this->log_term_change($term1, "field removed", $field, $this->old_dict, null);
                }
            }

            // Find new fields for this term
            $example_old_entry = $this->old_dict[array_key_first($this->old_dict)];
            $example_new_entry = $this->new_dict[array_key_first($this->new_dict)];
            $new_keys = array_diff_key($example_old_entry, $example_new_entry);
            
            foreach($new_keys as $field=>$in_new_dictionary) {
                if ($in_new_dictionary) {
                    $this->log_term_change($term2, "field added", $field, null, $this->new_dict[$term2]);
                }
            }
        }
    }

    function log_term_change($term, $type, $field, $old_data, $new_data) {
        $log = [];
        $log['type'] = $type;
        $log['term'] = $term;
        $log['field'] = $field;
        $log['message'] = match ($type) {
            
            'field updated' =>
                "&lsquo;".$field."&rsquo; updated from &lsquo;".
                $old_data."&rsquo; to &lsquo;".$new_data."&rsquo;",

            'field removed' =>
                "&lsquo;".$field."&rsquo; field removed, was &lsquo;".
                $old_data[$term][$field]."&rsquo;",

            'field added' =>
                "&lsquo;".$field."&rsquo; field added with value ".
                "&lsquo;".$new_data[$term][$field]."&rsquo;",

            'field renamed' => "&lsquo;".$field."&rsquo; field renamed to ?",

            'term removed' =>
                "Old term removed: ". implode(',', $old_data[$term]),

            'term added' =>
                "New term added: ". implode(',', $new_data[$term]),
            
            'term renamed' =>
                "Term renamed from &lsquo;".$old_data."&rsquo; to &lsquo;".$new_data."&rsquo;",
        };
        $this->changes[] = $log;
    }
}