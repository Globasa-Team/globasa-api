<?php

namespace globasa_api;

use Exception;
// Exceptions to the stress rules: one syllable words that have no stresses
define('WORDS_TO_SKIP', [
    "am", "bax", "cel", "ci", "cis", "de", "di",
    "dur", "e", "el", "em", "ex", "fal", "fe", "fol", "ger", "har", "hoy",
    "hu", "in", "ji", "kam", "ki", "kom", "ku", "kwas", "le", "mas", "na",
    "nor", "of", "or", "pas", "per", "por", "pro", "su", "tas", "tem", "ton",
    "tras", "wey", "xa", "yon"
]);

define('REPLACE_GLB_REGEX', ['/c/', '/j/', '/r/', '/x/', '/y/', '/h/']);
define('REPLACE_IPA',      ['t͡ʃ',  'd͡ʒ',   'ɾ',   'ʃ',   'j',   'x']);

define('STRESS_MARKER', "\u{02C8}");
define('DEMARC', "\u{001F}"); // Unicode/ASCII seperator character
define('NO_SHIFT_CHARS', ['a', 'e', 'i', 'o', 'u', '-']); // Don't shift past vowels or hyphens
define('ONSET_CONSONANTS', ['b', 'd', 'f', 'g', 'k', 'p', 't', 'v']);
define('CODA_CONSONANTS', ['c', 'x', 'j', 'l', 'm', 'n', 'r', 's', 'w', 'x', 'y', 'z']);
define('FINAL_VOWEL_REGEX', "/[aeiou](?!.*[aeiou])/i");
define('GLOBAL_VOWEL_REGEX', "/[aeiou]/i");

define('WORD_CHARS_REGEX', '/[^A-Za-z0-9 \-]/');
define('PAREN_UNDERSCORE_MARKDOWN_REGEX', '/[\[{\(_].*[\]}\)_]/U');

class Term_parser
{
    private $csv_columns = null;
    public $lang_sources = [];
    private $pd = null;
    private $log = null;
    private $current_slug = null;


    /**
     * Parser for terms in Google Sheets CSV.
     * 
     * @param array $fields list of fields from Google Docs CSV
     * @param mixed $pd     ParseDown markdown parser
     */
    public function __construct(array $fields)
    {
        global $cfg;
        $this->csv_columns = $fields;
        if (empty($fields)) return null;
        $this->log = $cfg['log'];
        $this->pd = $cfg['parsedown'];
    }



    /**
     * Parses an entry array from Google Sheets CSV, returns term data array.
     * 
     * @param array  $data  CSV row as array
     * @return array [$raw, $parsed, $csv]
     */
    public function parse_term(array $data)
    {
        $raw = [];
        $parsed = [];
        $csv = [];

        foreach ($data as $index => $datum) {
            if (!isset(COLUMN_MAP[$this->csv_columns[$index]])) {
                continue;
            }
            $field = COLUMN_MAP[$this->csv_columns[$index]];
            if (str_starts_with($field, 'trans')) {
                $lang = explode(" ", $field)[1];
                $raw['trans'][$lang] = htmlentities($datum);
            } else if (strcmp($field, "status") == 0) {
                $raw['status'] = filter_var($datum, FILTER_VALIDATE_BOOLEAN);
            } else {
                $raw[$field] = htmlentities($datum);
            }
            $csv[$field] = $datum;
        }
        
        
        
        if (empty($raw['term'])) return;

        $this->set_globasa_terms($raw, $parsed);
        $this->current_slug = $parsed['slug'];
        $this->create_ipa($raw, $parsed);
        
        $this->parse_basic_field('status', $raw, $parsed);
        $this->parse_basic_field('category', $raw, $parsed, true);
        $this->parse_basic_field('word class', $raw, $parsed, true);
        
        $this->parse_translations($raw, $parsed);
        $this->parse_entry_note(data:$raw, entry:$parsed);
        $this->set_natlang_terms(parsed:$parsed, raw:$raw);
        
        $this->parse_etymology($raw, $parsed);
        $this->parse_list_field('tags', $raw, $parsed);
        $this->parse_list_field('synonyms', $raw, $parsed);
        $this->parse_list_field('antonyms', $raw, $parsed);
        if (!empty($raw['example'])) {
            if (!isset($parsed['examples'])) {
                $parsed['examples'] = [];
            }
            $parsed['examples'][] = trim($raw['example']);
        }

        // $this->lint($parsed);

        return [$raw, $parsed, $csv];
    }



    /**
     * Find anomalies or errors not otherwise caught
     */
    private function lint($entry) {
        global $import_report;
    }




    /**
     * Parse basic field by getting the $raw data and copying it to $parsed.
     * 
     * @param string  $field   field to copy
     * @param array   $raw     raw data
     * @param array   $parsed  parsed data
     */
    private function parse_basic_field($field, $raw, &$entry, $log_empty = false)
    {
        if (!isset($raw[$field])) {
            $entry[$field] = "";
        } else if (is_string($raw[$field]))
            $entry[$field] = trim($raw[$field]);
        else
            $entry[$field] = $raw[$field];
    }



    /**
     * 
     */
    private function parse_entry_note(array &$entry, array &$data) {
        global $import_report;
        
        if (empty($data['entry note'])) return;

        $entry['entry note beta'] = $data['entry note'];
        
        $notes = explode('.', $data['entry note']);
        
        foreach($notes as $note) {

            if ($note==='Am oko tabellexi') {
                $entry['entry notes'][$note] = true;
                continue;
            } elseif (!str_contains($note, ':')) {
                $import_report[]=['term'=>$entry['slug'], 'msg'=>'Entry note error, content='.$note];
                $entry['entry notes']['Nota'] = $this->pd->line($note);
                continue;
            }
            
            [$keyword, $content] = explode(':', $note);
            $content = trim($content);

            switch ($keyword) {
                case 'am oko':
                case 'kurto lexi':
                case 'kompara':
                    foreach(explode(', ', $content) as $slug) {
                        $entry['entry notes'][$keyword][slugify($slug)] = null;
                    }
                    break;
                case 'Nota':
                    $entry['entry notes'][$keyword] = $this->pd->line($content);
                    break;
                case 'gramati':
                    $entry['entry notes'][$keyword] = $content;
                    break;
                default:
                    $import_report[]=['term'=>$entry['slug'], 'msg'=>'Entry note error, type='.$keyword];
                    $entry['entry notes']['Nota'] = $this->pd->line($keyword.': '.$content);
            }
            
        }
    }




    /**
     * Parse etymology string and similar words string.
     * 
     * 
     * Starts with kwasilexi 
     * Starts with Am pia oko
     * Starts with Am oko
     * 
     * Splits on ". ". Includes space for acronyms in example text.
     * 
     * 
     * @param array $raw     raw entry data
     * @param array $parsed  current parsed entry to save data to
     */
    private function parse_etymology(array $raw, array &$parsed)
    {
        global $import_report;

        if (empty($raw['etymology'])) {
            $import_report[] = ['term'=>$this->current_slug, 'msg'=>"Empty etymology"];
            return;
        }

        $etymologies = explode(". ", $raw['etymology']);
        foreach($etymologies as $cur) {

            $cur = trim($cur);
            //
            // Determine etymology type based on spreadsheet-format.md
            //
            if (empty($cur)) {
                continue;
            } else if (str_starts_with($cur, "https://") || str_starts_with($cur, "http://")) {
                if (!empty($parsed['etymology']['link'])) {
                    $this->log->add("ERROR: Term `".$raw['term']."` has duplicate linked etymology.");
                    $import_report[] = ['term'=>$this->current_slug, 'msg'=>"Duplicate linked etymology."];
                }
                $parsed['etymology']['link'] = $this->parse_etymology_linked($cur);
            } else if (strcmp($cur, "a priori") === 0) {
                $parsed['etymology']['a priori'] = true;
            } else if (str_starts_with($cur, "Am " )) {
                if (str_starts_with($cur, "Am oko " )) {
                    if (!empty($parsed['etymology']['am oko'])) {
                        $this->log->add("Error: Duplicate `am oko` in etymology.");
                        $import_report[] = ['term'=>$this->current_slug, 'msg'=>"Duplicate `am oko` in etymology."];
                    }
                    $parsed['etymology']['am oko'] = $this->parse_etymology_also_see($cur, 7);
                }
                else if (str_starts_with($cur, "Am kompara: " )) {
                    $parsed['etymology']['am kompara'] = $this->parse_etymology_also_see($cur, 12);
                }
                else {
                    $this->log->add("Error: Etymology starts with 'am ' but isn't.".$cur);
                    $import_report[] = ['term'=>$this->current_slug, 'msg'=>"One etymology starts with 'am ' but isn't."];
                }
            } else if (str_starts_with($cur, "kwasilexi - ")) {
                $parsed['etymology']['kwasilexi'] = $this->parse_etymology_natlang_freeform(substr($cur, 12), $parsed['slug']);
            } else if (str_contains($cur, "(")) {
                if (!empty($parsed['etymology']['natlang'])) {
                    $this->log->add("ERROR: Term `".$raw['term']."` has duplicate natlang etymology.");
                    $import_report[] = ['term'=>$this->current_slug, 'msg'=>"Duplicate natlang etymology."];
                }
                $parsed['etymology']['natlang'] = $this->parse_etymology_natlang_freeform($cur, $parsed['slug']);
            } else {
                // Assume it's derived
                if (!empty($parsed['etymology']['derived'])) {
                    $this->log->add("ERROR: Term `".$raw['term']."` has duplicate derived etymology.");
                    $import_report[] = ['term'=>$this->current_slug, 'msg'=>"Duplicate derived etymology."];
                }
                $parsed['etymology']['derived'] = $this->parse_etymology_derived($cur, $parsed['slug']);
            }
        }


        // Also check the similar natlang words
        if (isset($raw['similar natlang']) && !empty($raw['similar natlang'])) {
            $parsed['etymology']['natlang similar'] = $this->parse_etymology_natlang_freeform($raw['similar natlang'], $parsed['slug'], false);
        }
    }



    /**
     * Gets the list of terms with on of the also see word lists.
     */
    private function parse_etymology_also_see(string $etymology, int $skip):array {
        global $dict;

        $result = array();
        $references = explode(",", substr($etymology, $skip));
        foreach($references as $data) {
            if ( empty($data) ) continue;
            
            $slug = trim($data);
            if (str_ends_with($slug, '.')) {
                $slug = substr($slug, 0, -1);
            }
            $result[$slug] = $slug;
        }
        return $result;
    }



    /**
     * Parse derived etymology for derived, affix or phrase terms. Derived
     * etymology is a string of term slugs seperated by + or , characters.
     * White space is ignored.
     * 
     * Eg. "term1 + term2, term3"
     * 
     * Some terms may be a phrase with spaces seperating components. Ideally,
     * they would always be slugs. The term is sluggified.
     * 
     * Returns an array of each component. Eg:
     *  ["term1", "+", "term2", ",", "term3"]
     * 
     * @param string   $derived_etymology     the derived etymology string
     * @return string  formatted derived etymology string
     */
    private function parse_etymology_derived(string $derived_etymology)
    {
        global $derived_data;

        // Break in to fragments and rebuild fragment by fragment.
        $frag = explode(' ', $derived_etymology);
        $phrase = ''; // this is actually a term which might be a phrase, I think?
        $etymology_array = [];

        foreach ($frag as $word) {
            // Check if end of phrase (or term).
            // The end is reach when $word is a `+` or ends with a `,`
            $stop = '';

            // Find if we are stopping with a seperator
            if ($word == '+') {
                $word = '';
                $stop = '+';
            } else if (substr($word, -1) == ',') {
                $word = substr($word, 0, -1);
                $stop = ',';
            }

            // Add word to phrase
            if (!empty($phrase) && !empty($word)) {
                $phrase .= ' ';
            }
            $phrase .= $word;
            
            if ($stop) {
                // Finished phrase!

                // add to etymology result
                $etymology_array[] = $phrase;
                $etymology_array[] = $stop;
                
                // Record for backlinking
                $slug = slugify($phrase);
                $derived_data[$slug][] = $this->current_slug;
                
                $phrase = '';
                $stop = '';
            }
        }

        // Add final $phrase if it's a leftover
        // exactly as above else block
        if (!empty($phrase)) {
            // add to etymology result
            $etymology_array[] = $phrase;

            // Record backlink
            $slug = slugify($phrase);
            $derived_data[$slug][] = $this->current_slug;
        }

        return $etymology_array;
    }






    /**
     * Parse temporary link to Reddit.
     * 
     * @param  string   $etymology_link    URL of the proposed stymology.
     * @return string   URL
     */
    private function parse_etymology_linked(string $etymology_link)
    {
        $etymology_link = trim($etymology_link);
        if (str_ends_with($etymology_link, '.')) {
            $etymology_link = substr($etymology_link, 0, -1);
        }
        // Do an extra trim just in case they put a period after a space or something
        return $etymology_link;
    }



    



    /**
     * Parse borrowed etymology for root words and proper nouns.
     * 
     * @param string    $natlang_etymology     Source of the word in the form of `language (term; term, term)`
     * @return array    array of languages containing array of terms
     */
    private function parse_etymology_natlang_freeform(string $natlang_etymology, string $term, bool $mark_etymology = true)
    {
        global $import_report, $natlang_etymologies;

        $len = strlen($natlang_etymology);
        $at_seperator = false;
        $lang_start = 0;
        $enclosure_level = 0;
        $enclosure_start = 0;
        $enclosure_end = 0;
        $result = array();

        for ($pos = 0; $pos <= $len; $pos++) {
            if ($enclosure_level > 0 && $pos < $len) {
                switch ($natlang_etymology[$pos]) {
                    case '(':
                        $enclosure_level += 1;
                        break;
                    case ')':
                        $enclosure_level -= 1;
                        $enclosure_end = $pos;
                        break;
                    default:
                        break;
                }
            } else if ($pos < $len) {
                switch ($natlang_etymology[$pos]) {
                    case '(':
                        $enclosure_level = 1;
                        $enclosure_start = $pos;
                        $enclosure_end = $pos;
                        break;
                    case ',':
                        $at_seperator = true;
                        break;
                    default:
                        break;
                }
            }

            if ($at_seperator || $len <= $pos) {
                // Reached the end of an etymology

                if ($len > $pos && $enclosure_level > 0) {
                    $this->log->add("Error: Term `{$term}` has malformed etymology, missing `)`");
                    $import_report[] = ['term'=>$this->current_slug, 'msg'=>"Malformed etymology, missing `)`"];
                    $enclosure_end = $pos;
                }

                if ($enclosure_start != $enclosure_end) {
                    // If it includes enclosure, save example
                    $lang = trim(substr($natlang_etymology, $lang_start,  $enclosure_start - $lang_start));
                    $example = trim(substr($natlang_etymology, $enclosure_start + 1, $enclosure_end - $enclosure_start - 1));
                    if (str_ends_with($example, ".")) {
                        $example = substr($example, 0, -1);
                    }
                    $result[$lang] = $example;
                    $natlang_etymologies[$lang][] = $this->current_slug;
                } else {
                    // No enclusre, no example to save.
                    $lang = trim(substr($natlang_etymology, $lang_start, $pos-$lang_start));
                    // record language, unless it's etc (ji max to).
                    if (strcmp($lang, "ji max to") !== 0) {
                        $result[$lang] = "";
                        $natlang_etymologies[$lang][] = $this->current_slug;
                    }
                }

                // Error check the language name
                if (   str_contains($lang, '(') || str_contains($lang, ')') ||
                       str_contains($lang, ':') || str_contains($lang, ';') ||
                       str_contains($lang, '-') || str_contains($lang, '+') ||
                       str_contains($lang, ',') || str_contains($lang, '?')
                    ) {
                    $this->log->add("Etymology Error: Term `{$term}` has one of ():;-+,? in language name `$lang`. (Possibly caused by missing a comma from previous language?)");
                    $import_report[] = ['term'=>$this->current_slug, 'msg'=>"Natlang etymology has one of ():;-+,? in language name `{$lang}`. (Possibly caused by missing a comma from previous language?)"];
                }


                if (empty($lang)) {
                    $this->log->add("Etymology Error: Term `{$term}` has blank language name in it's natlang etymology.");
                    $import_report[] = ['term'=>$this->current_slug, 'msg'=>"Blank language name natlang etymology"];
                }

                $at_seperator = false;
                $lang_start = $pos + 1;
                $enclosure_start = 0;
                $enclosure_end = 0;
            }
            
        }

        ksort($result);
        return $result;
    }




    /**
     * Create IPA of Globasa word
     * 
     * @param array $raw     raw entry data
     * @param array $parsed  current parsed entry to save data to
     */
    private function create_ipa(array $raw, array &$parsed)
    {
        $phrase = [];
        foreach (explode(" ", $parsed['term']) as $cur) {
            $phrase[] = $this->add_ipa_stresses(strtolower($cur));
        }
        $parsed['ipa'] = preg_replace(REPLACE_GLB_REGEX, REPLACE_IPA, implode(" ", $phrase));
        $parsed['ipa link'] = "https://ipa-reader.com/?voice=Ewa&text=" . $parsed['ipa'];
    }


    /**
     * add ipa stresses to Globasa word. Must be in latin script and not
     * converted to IPA yet.
     * 
     * As per docuentation for JavaScript function AddStressToWord():
     * https://github.com/ShawnPConroy/WorldlangDict/blob/e10709c9fe319d991a9c787f46c2e1cbeaabc2b4/templates/menalar/js/ipa.js#L270

     * @param string  $word Globasa word in latin script to add stresses to
     * @return string word with stress markers added
     */
    public static function add_ipa_stresses($word = null)
    {
        // Skip Rule
        if (in_array($word, WORDS_TO_SKIP)) {
            return $word;
        } else if (empty($word)) {
            return "";
        }
        // Single vowel rule (or no vowels)
        $vowels = preg_match_all(GLOBAL_VOWEL_REGEX, $word);
        if ($vowels == false) {
            return $word;
        } else if ($vowels == 1) {
            return STRESS_MARKER . $word;
        }

        // Vowel Select Rule
        $wordlet = substr($word, 0, -1);
        preg_match(FINAL_VOWEL_REGEX, $wordlet, $match);

        $pos = strrpos($wordlet, $match[0]);
        $adj1 = $word[$pos - 1];
        $adj2 = $word[$pos - 2];

        // Shift Rules
        $shift = -1;

        if ($pos == 0 || in_array($adj1, NO_SHIFT_CHARS)) {
            $shift = 0;
        } else if (
            ($adj1 == 'y' || $adj1 == 'w') &&
            ($adj2 != 'y' && $adj2 != 'w' && !in_array($adj2, NO_SHIFT_CHARS))
        ) {
            $shift = -2;
        } else if (($adj1 == 'r' || $adj1 == 'l') &&
            in_array($adj2, ONSET_CONSONANTS)
        ) {
            $shift = -2;
        }

        // don't shift beyond the first letter
        if ($pos + $shift < 0) {
            $shift = -$pos;
        }

        return substr($word, 0, $pos + $shift) . STRESS_MARKER . substr($word, $pos + $shift);
    }



    /**
     * Parse field that is a comma seperated list.
     * 
     * @param string $field   field to be parsed
     * @param array  $raw     raw entry
     * @param array  $parsed  parsed entry
     */
    private function parse_list_field(string $field, $raw, &$parsed)
    {
        if (empty($raw[$field])) return;

        foreach (explode(',', $raw[$field]) as $datum) {
            $parsed[$field][] = trim($datum);
        }
    }




    /**
     * Parse language translation from Google Docs CSV. Render
     * individual tranlsation terms and search terms based on
     * translation terms.
     * 
     * - Any number of translation groups seperated by ;
     * - Any number of terms, seperated by ,
     * - Optional notes or clarifications in ( ) or _ _
     * 
     * Example:
     * 
     *      term1, term2; term3 (note1, note2, not3), term4
     * 
     * Warning: parenthetical notes may not use ;
     *      and may not have brackets () within brackets ()
     * 
     * @param array  $raw      $raw['trans'] the list of natlang terms
     * @param array  $parsed   the parsed entry being built
     */
    private function parse_translations(array &$raw, array &$parsed) {

        foreach($raw['trans'] as $lang => $translations) {
            
            $parsed['trans html'][$lang] = "";
            $parsed['trans'][$lang] = [];

            if (empty($translations)) {
                continue;
            }
            
            // For each language, save rich text string output
            $parsed['trans html'][$lang] = $this->pd->line($translations);
            
            // For each language, parse translations
            $translations = html_entity_decode($translations);
            $start = 0;
            $len = strlen($translations);
            $group_terms = [];
            
            for($pos = 0; $pos < $len; $pos++) {
                
                if ($translations[$pos]==='(') {
                    // Skip to end of enclosure, $pos is ')'
                    $pos = strpos($translations, ')', $pos);
                    if ($pos===false) {
                        $pos = $len;
                        $this->log->add("ERROR: Term `".$parsed['term']."` is missing a closing ')' in translation.");
                    }
                }
                
                if ($translations[$pos]==='[') {
                    // Skip to end of enclosure, $pos is ']'
                    $pos = strpos($translations, ']', $pos);
                    if ($pos===false) {
                        $pos = $len;
                        $this->log->add("ERROR: Term `".$parsed['term']."` is missing a closing ']' in translation.");
                    }
                }

                if($translations[$pos]===',') {
                    // save single term
                    $term = trim(substr($translations, $start, $pos-$start));
                    $group_terms[] = $this->pd->line($term);
                    self::set_natlang_term_from_translation(parsed:$parsed, lang:$lang, term:$term);
                    $start = $pos+1;
                } elseif($translations[$pos]===';') {
                    // end of group, save current group of terms
                    // save single term
                    $term = trim(substr($translations, $start, $pos-$start));
                    $group_terms[] = $this->pd->line($term);
                    $parsed['trans'][$lang][] = $group_terms;
                    $group_terms = [];
                    $start = $pos+1;
                    self::set_natlang_term_from_translation(parsed:$parsed, lang:$lang, term:$term);
                } elseif($pos >= $len-1) {
                    // end of translations, save current group of terms
                    // save single term
                    $term = trim(substr($translations, $start));
                    $group_terms[] = $this->pd->line($term);
                    $parsed['trans'][$lang][] = $group_terms;
                    $group_terms = [];
                    $start = $pos+1;
                    self::set_natlang_term_from_translation(parsed:$parsed, lang:$lang, term:$term);
                }
            }
        }
    }


    

    /**
     * Parse natlang terms and render them for search terms.
     */
    private function set_natlang_terms(array &$parsed, array $raw)
    {
        self::set_natlang_terms_manual(raw:$raw, parsed:$parsed);
        
        foreach($parsed['search terms'] as $lang=>$lang_terms) {
            $parsed['search terms'][$lang] = array_values(array_unique($lang_terms));
        }
    }

    /**
     * Adds manually entered English search terms.
     */
    private function set_natlang_terms_manual(array $raw, array &$parsed)
    {
        // Parse manual search terms (English only)
        if(empty($raw['search terms eng'])) {
            return;
        }

        foreach(explode(", ", $raw['search terms eng']) as $term) {
            $parsed['search terms']['eng'][] = $term;
        }

        return;
    }


    /**
     * Parse single term and add to search terms
     */
    private function set_natlang_term_from_translation(array &$parsed, string $lang, string $term) {

        if(empty($term)) return;

        // Remove notes
        $term = preg_replace('/\(_(.+)_\)/U', '', $term);     // (_ ... _)
        $term = preg_replace('/_\*\*(.+)\*\*_/U', '', $term); // _** ... **_
        $term = preg_replace('/\[.+\].+\]/U', '', $term);     // [...[...]...]
        // If we also need to do single bracket: /\[.+\]/U
        if (empty($term) || ($term[0] == '_' && $term[-1] != '_')) {
            // No non-note content
            return;
        }

        // included all parts, removing parentheses and underscores.
        $cur = trim(preg_replace('/[\(\)_]/U', '', $term));     // (_ ... _)
        // Remove brackets for SPA gender
        $cur = trim(preg_replace('/\[(.*)\]/U', '', $cur));     // [ ... ]
        $cur = strtolower(trim($cur));
        $parsed['search terms'][$lang][] = trim($cur);

        // Remove optional parts by deleting what is inside the
        // brackets and removing double white space.
        if (strpos($term, '(') !== false) {
            $cur = preg_replace('/\((.+)\)/U', '', $term);
            $cur = preg_replace('/\s\s+/', ' ', $cur);
            $cur = strtolower(trim($cur));
            $parsed['search terms'][$lang][] = trim($cur);
        }

        // Add search term for clarifying notes, such as 
        // `subordinate clause: where` for denloka hu
        if (strpos($term, ':') !== false) {
            $cur = substr($term, strpos($term, ':')+1);
            $parsed['search terms'][$lang][] = trim($cur);
        }
    }


    /**
     * Parse translation for search terms
     */
    // TODO: remove?
    private function set_natlang_terms_from_translation(array $parsed, array &$search_terms) {

        if(!isset($parsed['trans'])) return;

        foreach ($parsed['trans'] as $lang => $lang_trans) {
            foreach ($lang_trans as $trans_group) {
                foreach ($trans_group as $trans) {
                    // Remove notes
                    $trans = preg_replace('/\(_(.+)_\)/U', '', $trans);     // (_ ... _)
                    $trans = preg_replace('/_\*\*(.+)\*\*_/U', '', $trans); // _** ... **_
                    $trans = preg_replace('/\[.+\].+\]/U', '', $trans);     // [...[...]...]
                    // If we also need to do single bracket: /\[.+\]/U
                    if (empty($trans) || ($trans[0] == '_' && $trans[-1] != '_')) {
                        // No non-note content
                        continue;
                    }

                    // included all parts, removing parentheses and underscores.
                    $cur = trim(preg_replace('/[\(\)_]/U', '', $trans));     // (_ ... _)
                    $cur = strtolower(trim($cur));
                    $search_terms[$lang][] = trim($cur);

                    // Remove optional parts by deleting what is inside the
                    // brackets and removing double white space.
                    if (strpos($trans, '(') !== false) {
                        $cur = preg_replace('/\((.+)\)/U', '', $trans);
                        $cur = preg_replace('/\s\s+/', ' ', $cur);
                        $cur = strtolower(trim($cur));
                        $search_terms[$lang][] = trim($cur);
                    }
                }
            }
            
        }
    }


    /**
     * Renders term variation for the slug, worldlang search index, and
     * a smaller set for the mini def. This will add to the index:
     * the term as-is, the full term without brackets, the shortened term
     * without bracketted text, and all term fragments. Mini def will
     * not include fragments.
     * 
     * Eg: `(foo) bar grill` would add:
     *  (foo) bar grill
     *  foo bar grill
     *  bar grill
     *  bar
     *  grill
     * 
     * Eg. `(fe) ban leli watu` would add
     * 
     * min & index:
     *   (fe) ban leli watu
     *   fe ban leli watu
     *   ban leli watu
     * index for search only:
     *   ban
     *   leli
     *   watu
     * 
     */
    private function set_globasa_terms($raw, &$parsed)
    {
        global $cfg;

        $search_terms = [];
        $parsed['alt forms'] = [];

        // Trim for entry term (source sensitive)
        $parsed['term'] = trim($raw['term']);

        // Create slug, with modifier if needed
        $parsed['slug_mod'] = !empty($raw['slug_mod']) ? slugify($raw['slug_mod']) : '';
        $parsed['slug'] = slugify($parsed['term']) .
        (!empty($parsed['slug_mod']) ? '_'.$parsed['slug_mod'] : '');
        
        // Generate specified term
        if (!empty($parsed['slug_mod'])) {
            $parsed['term_spec'] = "{$parsed['term']} ({$parsed['slug_mod']})";
        } else {
            $parsed['term_spec'] = $parsed['term'];
        }

        // Create full search index term (lower case)
        $index = strtolower($parsed['term']);
        // Add full term to search terms
        $search_terms[] = $index;

        // If has optional part in round brackets, remove and add to index.
        // Also, add these alt forms to the alt form list.
        if (strpos($index, "(") !== false) {
            // Add the full term without brackets
            $cur = trim(preg_replace(WORD_CHARS_REGEX, '', $index));
            $search_terms[] = $cur;
            $parsed['alt forms'][] = $cur;
            // Adds shortened term, removing bracketed text
            $cur = trim(preg_replace(PAREN_UNDERSCORE_MARKDOWN_REGEX, '', $index));
            $search_terms[] = $cur;
            $parsed['alt forms'][] = $cur;
        }

        // Add all term fragments not in brackets
        $terms = explode(' ', preg_replace(PAREN_UNDERSCORE_MARKDOWN_REGEX, '', $index));
        foreach ($terms as $cur) {
            $term = trim($cur);
            if (empty($term)) continue;
            $search_terms[] = $term;
        }

        $parsed['search terms'][$cfg['wl_code_short']] = array_unique($search_terms);

    }
}
