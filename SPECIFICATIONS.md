# Comely YAML Specifications

* All YAML files MUST have either ".yaml" or ".yml" extension
* All YAML files and keys MUST BE named in alpha-numeric digits
* All YAML files and keys MAY have "." dot(s) , "-" dash(es) and "_" underscore(s)
* All strings values that MAY correspond to data types NULL and booleans SHOULD BE enclosed with in quotes (" or ')
* All strings that start with a quote MUST end with same quote

## Comments

* Comments must be suffixed with "#"

```yaml
ice-cream: no # This is an inline comment
# This is a full line comment
steaks: yes # Another inline comment
```

## NULLs

Value | Parsed As
--- | ---
*no value* | NULL
~ | NULL
"~" | string(1) "~"
null | NULL
NULL | NULL
"null" | string(4) "null"

## Evaluate Booleans

Evaluate booleans behaviour may be enabled optionally to automatically detect and convert values like "true", "false", 
"on", "off", "yes" and "no" to booleans.

### Enabling

```php
$parser = Yaml::Parse("some-yaml-file.yaml")
    ->evaluateBooleans();
```

**Note:** Enclosing such values with quotes (" or ') will not convert them to booleans. See table below:

Value | Parsed As
--- | ---
true | bool(true)
"true" | string(4) "true"
"false" | string(5) "false"
false | bool(false)
1 | int(1)
0 | int(0)
on | bool(true)
off | bool(false)
"off" | string(3) "off"
"yes" | string(3) "yes"
yes | bool(true)
no | bool(false)

## Long Strings

String values CAN BE split across multiple lines, provided that "|" or ">" is passed directly as value to 
corresponding key.

String values following in next lines after key MUST have higher indention then key it self, otherwise it will be 
parsed as NULL.

**">" will glue multi-line strings with a space**

```yaml
something: >
  The quick brown fox
  jumps over 
  the lazy dog
```
```
The quick brown fox jumps over the lazy dog
```

**"|" will glue multi-line strings with line breaks**

```yaml
something: |
  The quick brown fox
  jumps over 
  the lazy dog
```
```
The quick brown fox 
jumps over
the lazy dog
```

## Sequences

Yaml sequences will be parsed as arrays and all items will be pre-processed. Here is an example:

```yaml
basket:
  - apple
  - mango
  - 23.2
  - true
  - "true"
  - -1.33
  - 19
```
```
array(7) { [0]=> string(5) "apple" [1]=> string(5) "mango" [2]=> float(23.2) [3]=> bool(true) [4]=> string(4) "true" [5]=> float(-1.33) [6]=> int(19) } 
```

## Associative Arrays

```yaml
address:
  city: Islamabad
  lines: >
    Main Margalla Ave.
    Sector F-11/4
  state: ICT
  country: PK
  zip: 44000
```
```
array(5) { ["city"]=> string(9) "Islamabad" ["lines"]=> string(32) "Main Margalla Ave. Sector F-11/4" ["state"]=> string(3) "ICT" ["country"]=> string(2) "PK" ["zip"]=> int(44000) } 
```

## Imports

* All imports MUST BE defined as sequences in a key "imports"
* Only relative paths MUST BE used
* Values from imported files having same keys will be overwritten

---

*main.yml*
```yaml
imports:
  - app.yml
  - settings.yml

site:
  imports:
    - site/site-config.yml
    
  name: Comely
```

*app.yml*
```yaml
app:
 id: Test app
 version: 0.1.19
```

*settings.yml*
```yaml
settings:
 frontend: off
 backend: on
```

*site/site-config.yml*
```yaml
name: Untitled
url: https://comely.io
```

---

Parsed YAML var_dump:
```
array(3) {
  ["app"]=>
  array(2) {
    ["id"]=>
    string(8) "Test app"
    ["version"]=>
    string(6) "0.1.19"
  }
  ["settings"]=>
  array(2) {
    ["frontend"]=>
    bool(false)
    ["backend"]=>
    bool(true)
  }
  ["site"]=>
  array(2) {
    ["name"]=>
    string(6) "Comely"
    ["url"]=>
    string(17) "https://comely.io"
  }
}
```