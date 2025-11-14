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
gram_cat1: term11 (specifier), term12, term13 (_translation note_); gram_cat: term21, term22, term23...; gram_cat3: term31, (specifier) term32, term33 (specifier)
```

- Grammatical category groups
  - The translation column is broke down in to grammatical category groups by the semicolon (`;`).
  - If there is no `;` then all translation are part of the same group.
  - The grammatical category of the group can be defined before a colon (`:`). Eg. word class or clause type, etc.
  - If not defined, assume the first undefined group is noun, and the second is verb if there are two undefined groups. Always using English 'noun' and 'verb', even when not a English translation column. TODO
  - Tip: Add a single semicolon like `noun;` and `;verb` to create an empty group and save typing for English translations. TODO
- Terms
  - Each term is separated by commas (`,`).
  - semantic labels: The term may have round brackets and underscores (`(_ _)`) that enclose text containing a semantic label or clarification. Used to clarify ambiguity, meaning or use.
    - `swallow (_bird_)`
    - `ah (_denotes surprise or wonder_)`
  - translation element: the term may have round brackets that enclose a translation element (component of a phrase) to add phrases to the index. May be before or after or both. Eg:
    - `(made of) crystal` will add 'made of crystal' and 'crystal' to the search index.
    - `wish (for)` will add 'wish' and 'wish for' to the search index.
    - `(yyy) xxx (zzz)` will add `yyy xxx zzz` and `xxx` to the search index
  - Tip: May use markdown. For example when term is a scientific or borrowed word use underscores to make it italic.

Dev design:

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