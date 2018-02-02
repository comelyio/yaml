# Comely YAML Specifications

* All YAML files MUST have either ".yaml" or ".yml" extension
* All YAML files and keys MUST BE named in alpha-numeric digits
* All YAML files and keys MAY have "." dot(s) , "-" dash(es) and "_" underscore(s)
* All strings values that MAY correspond to data types NULL and booleans SHOULD BE enclosed with in quotes (" or ')
* All strings that start with a quote MUST end with same quote

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

### Types

">" will glue multi-line strings with a space " "

```yaml
something: >
  The quick brown fox
  jumps over 
  the lazy dog
```
```
The quick brown fox jumps over the lazy dog
```


"|" will glue multi-line strings with line breaks

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

### Values

### Imports

