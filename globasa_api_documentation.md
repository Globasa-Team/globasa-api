# Globasa API Documentation

API Location: `https://cdn.globasa.net/api2/`

The Globasa word list backend downloads the Globassa word list spreadsheet, parses that data, and writes out a series of files that can be used by other applications.

## End user files

The CSV, etc.

## Globasa Index

For a simple list of all Globasa terms, use the index:

* `index.json`
* `index.yaml`

They contain a key/value lookup where all entries are listed with a value of null, and some terms are listed with value indicating to connoncial term. For example, `ban leli watu` gives the cononcial index/slug `(fe) ban leli watu`, which can be looked up in the minimum translations file or basic translation file.

## Minimal translations files

Each language has it's own key/value look up file. The key is the index of a word and the value contains the word class in `<em>` tags and the translation. For example, the index `-cu` has a value of `<em>(b.nenoj xfik)</em> <em>intransitive verb marker</em>, become, get, turn (into)`

As of this time, only eng, epo and spa languages are supported:

* `basic_eng.json`
* `basic_eng.yaml`
* `basic_epo.json`
* `basic_epo.yaml`
* `basic_spa.json`
* `basic_spa.yaml`

## Basic translation files

The basic translation files provide most of what one needs to have a functioning app use Globasa. It contains the term as an index, and then sub fields for class, category and translation.

```
kanada:
  class: su n
  category: proper word
  translation: Canada
```









----


backlinks.yaml

basic_eng.json
basic_eng.yaml
basic_deu.json
basic_deu.yaml
basic_epo.json
basic_epo.yaml
basic_fra.json
basic_fra.yaml
basic_rus.json
basic_rus.yaml
basic_spa.json
basic_spa.yaml
basic_zho.json
basic_zho.yaml


min_deu.json
min_deu.yaml
min_eng.json
min_eng.yaml
min_epo.json
min_epo.yaml
min_fra.json
min_fra.yaml
min_rus.json
min_rus.yaml
min_spa.json
min_spa.yaml
min_zho.json
min_zho.yaml

search_terms_deu.json
search_terms_deu.yaml
search_terms_eng.json
search_terms_eng.yaml
search_terms_epo.json
search_terms_epo.yaml
search_terms_fra.json
search_terms_fra.yaml
search_terms_glb.json
search_terms_glb.yaml
search_terms_rus.json
search_terms_rus.yaml
search_terms_spa.json
search_terms_spa.yaml
search_terms_zho.json
search_terms_zho.yaml

stats.yaml
tags.json
tags.yaml