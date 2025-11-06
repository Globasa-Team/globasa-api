# Worldlang Dictionary import documentation


## Command line arguments

- v set $verbose_mode
- d set $debug_mode: only write some entries
- l use last CSV file (reprocess)
- p use production/api `word-list.csv`
- a use dev/api `word-list.csv` (may be outdated)
- c skip old CSV load & comparison
- w skip writing files
- --file specify input file


## Translation columns

Translation columns should be of the form:

```
word_class1: term11 (specifier), term12, term13 (_translation note_); word_class2: term21, term22, term23...; word_class3: term31, (specifier) term32, term33 (specifier)
```

* Translation notes are put in round parentheses with italic underscores. Used to clarify ambiguity, meaning or use.
   - swallow (_bird_)
   - ah (_denotes surprise or wonder_)
   - (made of) crystal - crystal or made of crystal

specifier is after and in italics. to include phrase fragments before or after. Allows better searching I think?



Notes:
- May use markdown. For example when term is a scientific or borrowed word.

1. `parse_translations()` is called on each entry during initial parsing.
2. For each language of `trans` parse the translations.
   1. Iterate thru character by character, building up segments.
   2. On open parenthesis, jump to end parenthesis.
   3. On open bracket, jump to end bracket.
   4. On comma, add current term to group.
   5. On semi colon, add current term to group, add group to entry, start new group.
   6. If on or past final character, ad current term to group, add group to entry, start new group.

To do:
- make this multibyte.
- make this save word class.
- Remove square brackets