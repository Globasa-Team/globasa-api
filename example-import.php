<?php

/**
 * Parse example sentences
 * 
 * Takes in source documents and parses content to create sentences
 * listing for each term.
 * 
 * Limitations: homonyms, and with auxilary documents english words
 * that are also in the worldlang index.
 */

declare(strict_types=1);

namespace WorldlangDict\Examples;

require_once('src/example.php');

\pard\app_start(true);


/*******************************
 * INIT
 * *****************************
 */
\pard\sec("Initiating script");
$pd = new \Parsedown();
\pard\m('Load configuration');
$cfg = yaml_parse_file('config/config-example.yaml');
\pard\m("load term index");
$wld_index = yaml_parse_file($cfg['worldlang_index']);
$examples = [];
\pard\end("initiation complete");


/**************************
 * Start
 * ************************
 */
import_example_sentences();
\pard\app_finished();
