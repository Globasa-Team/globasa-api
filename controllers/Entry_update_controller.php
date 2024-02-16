<?php
namespace globasa_api;
use Exception;
use Throwable;

class Entry_update_controller {

    // Microseconds (1 millions of a second)
    const TINY_IO_DELAY = 5000; // 5k microseconds = a twohundredths of a second

    const VALID_WORD_CATEGORIES = array(
        'root', 'proper word', 'derived', 'phrase', 'affix'
    );



    /**
     * 
     * Rule 1 & 2: Assumes you are not sending any phrases or affixes as $rhyme!
     */
    private static function add_entry_rhyme($entry, $rhyme) {
        global $dict;
        
        if (
            $dict[$rhyme]['category'] === 'affix' ||  // 1i
            $dict[$rhyme]['category'] === 'phrase' || // 1ii
            $entry === $rhyme                           // 2iii
        ) return;
        
        
        // Fetch entry final morpheme
        if (isset($dict[$entry]['etymology']['derived'])) {
            $entry_final_morpheme = $dict[$entry]['etymology']['derived'][array_key_last($dict[$entry]['etymology']['derived'])];
        } else {
            $entry_final_morpheme = $entry;
        }

        // Fetch entry final morpheme
        if (isset($dict[$rhyme]['etymology']['derived'])) {
            $rhyme_final_morpheme = $dict[$rhyme]['etymology']['derived'][array_key_last($dict[$rhyme]['etymology']['derived'])];
        } else {
            $rhyme_final_morpheme = $rhyme;
        }
        
        // Rhyme alt form
        if ($rhyme_final_morpheme[0] === '-') {
            $rhyme_final_morpheme_alt = substr($rhyme_final_morpheme, 1);
        } else {
            $rhyme_final_morpheme_alt = '-'.$rhyme_final_morpheme;
        }
        
        if (
            $entry_final_morpheme === $rhyme_final_morpheme ||  // 2i & 2ii
            $entry_final_morpheme === $rhyme_final_morpheme_alt // 3i, 3ii, 3iii
        ) return;


        // If still here, copy data
        $dict[$entry]['rhyme'][$rhyme]['word class'] = $dict[$rhyme]['word class'];
        $dict[$entry]['rhyme'][$rhyme]['term'] = $dict[$rhyme]['term'];
        // Copy all translations
        foreach($dict[$rhyme]['trans html'] as $lang=>$trans) {
            $dict[$entry]['rhyme'][$rhyme][$lang] = $trans;
        }

    }



    /**
     * Calculate stats from the dictionary entries.
     */
    public static function calculate_stats(): void {
        global $dict, $stats;

        $max_examples = 0;
        $max_examples_term = "";

        pard("calculate stats");
        $stats['etymology source percent'] = [];
        $stats['natlang roots'] = 0;

        foreach($dict as $slug=>$entry) {
            
            if (isset($entry['examples']) && count($entry['examples']) > $max_examples) {
                $max_examples = count($entry['examples']);
                $max_examples_term = $entry['slug'];
            }

            if ($entry['category'] === 'root' && array_key_exists('natlang', $entry['etymology']) ) {

                // Calculate the total number of roots and roots for each source lang
                $stats['natlang roots'] += 1;

                foreach($entry['etymology']['natlang'] as $natlang => $data) {
                    if (!array_key_exists($natlang, $stats['etymology source percent'])) {
                        $stats['etymology source percent'][$natlang] = 0;
                    }
                    $stats['etymology source percent'][$natlang] += 1;
                }
            } elseif ($entry['category'] === 'root' && array_key_exists('kwasilexi', $entry['etymology']) ) {
    
                // Calculate the total number of roots and roots for each source lang
                $stats['natlang roots'] += 1;

                foreach($entry['etymology']['kwasilexi'] as $natlang => $data) {
                    if (!array_key_exists($natlang, $stats['etymology source percent'])) {
                        $stats['etymology source percent'][$natlang] = 0;
                    }
                    $stats['etymology source percent'][$natlang] += 1;
                }
            }

        }

        // Calculate percentages
        foreach($stats['etymology source percent'] as $natlang=>$count) {
            $stats['etymology source percent'][$natlang] = round($count/$stats['natlang roots']*100, 2);
        }
        arsort($stats['etymology source percent']);

        pard("Most examples: ".$max_examples_term." with ".$max_examples);
    }



    private static function finalize_entry_data() {
        // Insert data that needed all entries to be loaded
        pard_sec("Finalize entries");
        self::insert_derived_terms();
        self::update_derived_etymology();
        self::update_entry_rhymes();
        self::update_entry_notes();
        pard_end();
    }




    /**
     * Renders the basic entry for each language. Includes:
     *  term, class, category, translations.
     */
    private static function insert_basic_entry(array $parsed) {
        global $basic_entries;

        if (!isset($parsed['trans html'])) {
            pard("Warning: missing trans html on ".$parsed['slug']);
            return;
        }

        foreach($parsed['trans html'] as $lang=>$translation) {
            $basic_entries[$lang][$parsed['slug']] = array();
            $basic_entries[$lang][$parsed['slug']]['term'] = $parsed['term'];
            $basic_entries[$lang][$parsed['slug']]['class'] = $parsed['word class'];
            $basic_entries[$lang][$parsed['slug']]['category'] = $parsed['category'];
            $basic_entries[$lang][$parsed['slug']]['translation'] = $translation;
        }
    }
    


    /**
     * Insert derived term data
     */
    public static function insert_derived_terms() {
        global $cfg, $dict, $import_report, $derived_data;
        pard("Derived terms");

        foreach($derived_data as $root=>$terms) {
            // For each root, find all derived terms
            foreach($terms as $term) {

                // Skip if word doesn't exist
                if (!array_key_exists($root, $dict)) {
                    $cfg['log']->add("Attempted to link entry `{$root}` to `{$term}`, but it doesn't exist.");
                    $import_report[] = ['term'=>$root, 'msg'=>"Term missing. Was linking from `{$term}`."];
                    continue;
                }

                // Copy derived term class to root
                $dict[$root]["derived terms"][$term]['class'] = $dict[$term]['word class'];
                $dict[$root]["derived terms"][$term]['term'] = $dict[$term]['term'];

                // Copy derived term translation data to root
                foreach($dict[$term]['trans html'] as $lang=>$translation) {
                    $dict[$root]['derived terms'][$term]['trans'][$lang] = $translation;
                }
            }
        }
    }



    private static function insert_examples(array &$entry) {
        global $examples;

        if (!isset($examples[$entry['slug']])) {
            return;
        }

        if (isset($entry['examples'])) {
            $entry['examples'] = array_merge($entry['examples'], $examples[$entry['slug']]);
        } else {
            $entry['examples'] = $examples[$entry['slug']];
        }
    }


    /**
     * Renders minimum definitions for the current term
     * and adds them to the array of mini defs.
     */
    private static function insert_minimum_entry(array $parsed) {
        global $min_entries;

        foreach($parsed['trans html'] as $lang=>$trans) {
            $min_entries[$lang][$parsed['slug']] = '<em>(' . $parsed['word class'] . ')</em> ' . $trans;
        }
    }



    /**
     * For each languages, add that languages search terms to the
     * search term array for that languages.
     */
    private static function insert_search_terms(array $parsed) {
        global $search_terms;
        foreach ($parsed['search terms'] as $lang => $terms) {
            foreach ($terms as $term) {
                $search_terms[$lang][$term][] = $parsed['slug'];
            }
        }
    }


    /**
     * 
     */
    private static function insert_standard_entry(array $entry) {
        global $standard_entries;

        $standard_entries[$entry['slug']] = [
            'term'=>$entry['term'],
            'word class'=>$entry['word class'],
            'category'=>$entry['category'],
            'trans'=>$entry['trans'],
            'etymology'=>$entry['etymology']
        ];
    }



    /**
     * Renders tags for the current term
     * and adds them to the array of tags.
     */
    private static function insert_tags(array $parsed) {
        global $tags;
        if (array_key_exists('tags', $parsed)) {
            foreach ($parsed['tags'] as $tag) {
                $tags[$tag][] = $parsed['slug'];
            }
        }
    }




    /**
     * For each languages add all term forms (the term and it's alt forms, if any)
     * to the index of all words.
     */
    private static function insert_term_index(array $parsed) {
        global $term_index;
        $term_index[$parsed['slug']] = [];
        foreach($parsed['alt forms'] as $alt) {
            $term_index[$alt] = $parsed['slug'];
        }
    }


    static function lint_entry(&$entry) {
        global $import_report, $dev_report;

        foreach($entry['trans html'] as $lang=>$translation) {
            if (str_contains($translation, ":</em>")) {
                $import_report[] = ['term'=>$entry['slug'], 'msg'=>"Linter Notice: {$lang} has colon inside italic rathre than outside"];
            }
        }
        if ($entry['category'] === 'derived' || $entry['category'] === 'phrase') {
            if (!isset($entry['etymology']['derived']) || empty($entry['etymology']['derived'])) {
                $import_report[] = ['term'=>$entry['slug'], 'msg'=>'Linter Notice: category is derived or phrase but no derived etymology detected.'];
            }
        }

    }


    static function parse_spreadsheet_data($term_stream) {
        global $new_csv_data, $dict, $debug_data;

        // Download the official term list, processing each term.
        $tp = new Term_parser(fields:fgetcsv($term_stream));

        pard_counter_start("Parsing spreadsheet terms");
        while(($data = fgetcsv($term_stream)) !== false) {
            // Parse term if it exists
            if (empty($data) || empty($data[0]) ) {
                continue;
            }
            
            [$raw_entry, $entry, $csv_row] = $tp->parse_term($data);

            $new_csv_data[$entry['slug']] = $csv_row;
            $debug_data[$entry['slug']] = $raw_entry;
            if (isset($entry['etymology'][')'])) unset($entry['etymology'][')']);
            
            // Insert entry in aggregate data
            self::insert_term_index($entry);
            self::insert_search_terms($entry);
            self::insert_basic_entry($entry);
            self::insert_minimum_entry($entry);
            self::insert_standard_entry($entry);

            self::insert_tags(parsed:$entry);
            self::insert_examples($entry);
            self::validate_and_count_category($entry['category'], $entry['term']);
            self::update_rhyme_data($entry);

            self::lint_entry($entry);

            $dict[$entry['slug']] = $entry;
            usleep(SMALL_IO_DELAY);
            pard_counter_next();
        }
        
        pard_counter_end();
        pard_end();
    }



    /**
     * Compare the new and old word list and log any changes.
     */
    static function log_changes() {
        global $cfg;
        // Find changes
        $comparison = new Dictionary_comparison();
        // Log changes
        $log = new Dictionary_log($cfg);
        $log->add($comparison->changes);
        $cfg['log']->add("Changes logged: ".count($comparison->changes));
    }




    /**
     * Updates the etymology/derived field to include translations
     */
    private static function update_derived_etymology() {
        global $dict;
        pard("Derived etymology");


        foreach($dict as $slug=>$entry) {
            if (!isset($entry['etymology']['derived'])) continue;

            foreach($entry['etymology']['derived'] as $part) {
                $part_slug = slugify($part);

                if ((strlen($part) === 1 && !ctype_alnum($part)) || !isset($dict[$part_slug])) {
                    // If it's a '+' or ','
                    $dict[$slug]['etymology']['derived trans'][] = ['text'=>$part];
                } else {
                    $dict[$slug]['etymology']['derived trans'][] = [
                        'slug'=>$part_slug,
                        'text'=>$dict[$part_slug]['term'],
                        'word class'=>$dict[$part_slug]['word class'],
                        'trans'=>$dict[$part_slug]['trans html']
                    ];
                }
                
            }
        }
    }


    static function update_entries(string $current_csv_filename, string $old_csv_filename) {
        global $cfg, $old_csv_data;

        pard_sec("Update entries");
        
        // Load old
        pard("Loading old CSV");
        load_csv($old_csv_filename, $old_csv_data);

        // Load and parse new data
        pard("Loading current terms");
        $term_stream = fopen($current_csv_filename, "r")
            or throw new Exception("Failed to open ".$current_csv_filename);
        self::parse_spreadsheet_data($term_stream);

        if (!feof($term_stream)) {
            $cfg['log']->add("Unexpected fgetcsv() fail");
        }
        fclose($term_stream);
        

        // Add cross referenced data to entries
        self::finalize_entry_data();

        // Check for changes
        pard_sec("Post entry update");
        pard("Logging changes");
        Entry_update_controller::log_changes();
        pard("Calculating stats");
        Entry_update_controller::calculate_stats();
        pard_end();

        // Write dictionary files
        File_controller::write_api2_files();
    }

    /**
     * Update notes for all entries with cononical terms
     */
    private static function update_entry_notes() {
        global $dict;

        foreach($dict as $term=>$entry) {
            foreach($entry['entry note'] as $keyword=>$data) {
                if (
                    $keyword==='Am oko' || $keyword==='Kurto lexi cel' ||
                    $keyword==='Am kompara fe' || $keyword==='Yongudo sol ton'
                ){
                    foreach($data as $slug=>$null_data) {
                        $dict[$term]['entry notes'][$keyword][$slug] = $dict[$slug]['term'];
                    }
                }
            }
        }
    }


    /**
     * Go through each rhyme ending group, and add
     * each term to each term, skipping inappropriate terms
     * as done in `self::add_entry_rhyme()`
     */
    private static function update_entry_rhymes() {
        global $rhyme_data, $dict;
        pard("Update entry rhymes");
        pard_progress_start(count($rhyme_data), "rhyme groups");

        // Go through each rhyme group to copy rhyming terms in to entry
        foreach($rhyme_data as $ending_group) {
            // skip if there are no rhymes
            if(count($ending_group) < 2) continue;

            sort($ending_group);

            foreach($ending_group as $entry) {

                if ($dict[$entry]['category'] === 'phrase') continue;
                
                foreach($ending_group as $rhyme) {
                    self::add_entry_rhyme($entry, $rhyme);
                }

                if ($entry[0]==='-') {
                    $alt = substr($entry, 1);
                } else {
                    $alt = '-'.$entry;
                }

                // Fetch entry final morpheme
                if (isset($dict[$entry]['etymology']['derived'])) {
                    $final_morpheme = $dict[$entry]['etymology']['derived'][array_key_last($dict[$entry]['etymology']['derived'])];
                } else {
                    $final_morpheme = $entry;
                }

                if ($final_morpheme[0]==='-') {
                    $alt = substr($final_morpheme, 1);
                } else {
                    $alt = '-'.$final_morpheme;
                }

                
                if(isset($dict[$alt])) {
                    $dict[$entry]['rhyme exclusions'] = [$final_morpheme, $alt];
                } else {
                    $dict[$entry]['rhyme exclusions'] = [$final_morpheme];
                }
                
            }
            pard_progress_increment();
            usleep(100);
        }
        pard_progress_end();
    }


    /**
     * Collecting rhyming data. A rhyme is the last two letters matching.
     * If there are no vowels, use 3.
     */
    private static function update_rhyme_data(array $entry):void {
        global $rhyme_data;

        $group = substr($entry['slug'], -2);

        if(!preg_match(GLOBAL_VOWEL_REGEX, $group)) {
            // If it does not have vowels use 3 letters
            $group = substr($entry['slug'], -3);
        }

        $rhyme_data[$group][] = $entry['slug'];
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
    static function validate_and_count_category(string $cat, string $word) {

        global $cfg, $import_report, $category_count;

        if (!in_array($cat, self::VALID_WORD_CATEGORIES)) {
            $cfg['log']->add("Word List Error: Invalid category `$cat` on term `$word`");
            $import_report[] = ['term'=>$word, 'msg'=>"Word List Error: Invalid category `$cat`"];
        }

        self::validate_and_count($cat, $category_count);
    }
}