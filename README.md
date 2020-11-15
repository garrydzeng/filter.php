# A Filter & Generator Library

We create a data format (called "PM1") that used to describe data structure.

## Language Guide

see `doc/PM1.md` for more information

## Example

```
<?php

// instantiate with specified cache directory
$example = new Filter('./var');

try {

  // object must represent as associative array in this library (whatever text are)
  $example->filter('/path/to/some.pm1', ... );
}
catch (Exception $exception) {
  ...
}
```