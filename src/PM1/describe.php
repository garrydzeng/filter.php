<?php
namespace GarryDzeng\PM1 {

  use InvalidArgumentException;

  function print_object($struct) {

    $pieces = [];

    foreach ($struct as [
      'value'=> $value,
      'is_optional'=> $optional,
      'name'=> $name,
    ])
    {
      $pieces[] = ($optional ? "$name?: " : "$name: ").describe($value);
    }

    return '{ '
      .implode(', ', $pieces).
    ' }';
  }

  function print_enumeration($struct) {
    return '('
      .implode(',', $struct).
    ')';
  }

  function print_array($struct) {

    switch ($struct) {

      case PM1_INT: return '[int]';
      case PM1_DOUBLE: return '[double]';
      case PM1_BYTE: return '[byte]';
      case PM1_STRING: return '[string]';
      case PM1_BOOL: return '[bool]';

      default: {

        [
          'definition'=> $what,
          'body'=> $body,
        ] = $struct;

        switch ($what) {

          case PM1_ENUMERATION: return print_enumeration($body);
          case PM1_OBJECT: return print_object($body);

          default: {
            throw new InvalidArgumentException(<<<Message
              Invalid notation found,
              An array must contain a primitive type, enumeration or object, 
              please check.
              Message
            );
          }
        }
      }
    }
  }

  function print_regular_expression($struct) {

    [
      'pattern'=> $pattern,
      'flag'=> [
        'global'=> $global,
        'case_insensitive'=> $caseInsensitive,
        'multi'=> $multi,
      ]
    ] = $struct;

    $modifier = '';

    if ($global) $modifier .= 'g';
    if ($caseInsensitive) $modifier .= 'i';
    if ($multi) $modifier .= 'm';

    return "/$pattern/$modifier";
  }

  function print_range($struct) {

    [
      'keyword'=> $keyword,
      'range'=> [
        'minimal'=> $minimal,
        'maximal'=> $maximal,
      ]
    ] = $struct;

    switch ($keyword) {

      case PM1_STRING: $keyword = 'string'; break;
      case PM1_INT: $keyword = 'int'; break;
      case PM1_DOUBLE: $keyword = 'double'; break;

      default: {
        throw new InvalidArgumentException(

        );
      }
    }

    return isset($maximal) ?  "$keyword<$minimal,$maximal>" : (isset($minimal) ? "$keyword<$minimal>" : $keyword);
  }

  function describe($struct) {

    [
      'definition'=> $definition,
      'body'=> $body,
    ] = $struct;

    switch ($definition) {

      case PM1_DATE: return 'date';
      case PM1_DATETIME: return 'datetime';
      case PM1_TIME: return 'time';

      case PM1_INT: return 'int';
      case PM1_DOUBLE: return 'double';
      case PM1_BYTE: return 'byte';
      case PM1_STRING: return 'string';
      case PM1_BOOL: return 'bool';

      case PM1_ARRAY: return print_array($body);
      case PM1_ENUMERATION: return print_enumeration($body);
      case PM1_OBJECT: return print_object($body);
      case PM1_REGULAR_EXPRESSION: return print_regular_expression($body);
      case PM1_RANGE: return print_range($body);

      default: {
        throw new InvalidArgumentException(<<<Message
          Invalid notation found,
          please check.
          Message
        );
      }
    }
  }
}