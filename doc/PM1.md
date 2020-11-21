# Primitive types

This format has 8 primitive types ...

- `date` a string like "YYYY-MM-DD"
- `datetime` a string like "YYYY-MM-DD HH:mm:ss" (ISO-8601 supported)
- `time` a string like "HH:mm:ss"
- `int` integer value
- `double` floating-point numbers
- `bool` true or false
- `string` a sequence of character (from 0 to 65535) values
- `byte` from 0 to 255

The `int`, `double` & `string` supports specify a range after keyword ...

```
double<-1.2,1.2>
int<0,1>
```

it follows ...

- enclose by "<" and ">"
- minimal or maximal value is depends on keyword (like double uses 1.2)
- use "," to separates two values
- value is optional 

_Last 1 rule means `double<,>` is legal !!!_

# Object

An object is an unordered set of name/value pairs ...

- enclose by "{" and "}"
- separates name/value by ":"
- name allows alpha, digit or underscore but first character must be letter (case-sensitive) ...
- append "?" to name to be optional 
- value can be primitive, object, enumeration, regular expression or array ...
- pair separates by ","

```
{
  id: int,
  name: string,
  account_balance: double,
  feature?: [string],
  type: (
    C1 = 1,
    C2 = 2,
    C3 = 3,
    4 
  )
}
```

# Enumeration

An enumeration is a set of named integer constants

- enclose by "(" and ")"
- separates name/value by "="
- name is optional and allows alpha, digit or underscore but first character must be letter (case-sensitive) ...
- value must be integer (positive or negative)
- pair separates by ","

```
(
  C1 = 1,
  C2 = 2,
  C3 = 3,
  4 
)
```

_last constant has no name_

# Regular Expression

See [Regular Expressions](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions), Now supports three modifiers ...

- `g` : global
- `i` : case-insensitive
- `m` : multi

# Array

An array is an ordered collection of values

- enclose by " [ " and " ] "
- it must contain one primitive, enumeration or object value (no more)
- single dimensional

```
[string]
```
