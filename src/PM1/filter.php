<?php
namespace GarryDzeng\PM1 {

  use InvalidArgumentException;
  use DateTime;

  function check_object($struct, $data, $depth = []) : array {

    $data = (array)$data;

    [
      'body'=> $properties,
    ] = $struct;

    foreach ($properties as [
      'value'=> $child,
      'is_optional'=> $optional,
      'name'=> $name,
    ])
    {

      [
        // Extract details by a name
        $name => $value,
      ] = $data;

      $success = $optional && null === $value;

      // optional but value present
      if (!$success) {

        [
          // always execute if value present whatever it marked as optional
          'success'=> $success,
          'declaration'=> $declaration,
          'depth'=> $extra
        ] = check_value($child, $value, [
          ...$depth,
          $name
        ]);

        if (!$success) {
          return [
            'success'=> false,
            'declaration'=> $declaration,
            'depth'=> $extra
          ];
        }
      }
    }

    return [
      'success'=> true,
      'declaration'=> $struct,
      'depth'=> $depth
    ];
  }

  function check_enumeration($struct, $data, $depth = []) {

    [
      'body'=> $values,
    ] = $struct;

    return [
      'success'=> in_array($data, $values, true),
      'declaration'=> $struct,
      'depth'=> $depth
    ];
  }

  function check_array($struct, $data, $depth = []) {

    if (!is_array($data)) {
      return [
        'success'=> false,
        'declaration'=> $struct,
        'depth'=> $depth
      ];
    }

    [
      'body'=> $element,
    ] = $struct;

    foreach ($data as $index => $value) {

      // Prepare for primitive
      $extra = $depth;

      // Element of array allows primitive, object and enumeration
      switch ($element) {

        case PM1_INT: $success = is_int($value); break;
        case PM1_DOUBLE: $success = is_double($value) || is_int($value); break;
        case PM1_BYTE: $success = is_int($value) && ($value >= 0 && $value <= 255); break;
        case PM1_STRING: $success = is_string($value); break;
        case PM1_BOOL: $success = is_bool($value); break;

        default: {

          [
            'definition'=> $what,
          ] = $element;

          switch ($what) {
            case PM1_ENUMERATION: $future = check_enumeration($element, $value, [...$depth, $index]); break;
            case PM1_OBJECT: $future = check_object($element, $value, [...$depth, $index]); break;
            default: {
              throw new InvalidArgumentException();
            }
          }

          [
            'success'=> $success,
            'declaration'=> $declaration,
            'depth'=> $extra
          ] = $future;
        }
      }

      if (!$success) {
        return [
          'success'=> false,
          'declaration'=> $declaration ?? $struct,
          'depth'=> $extra
        ];
      }
    }

    return [
      'success'=> true,
      'declaration'=> $struct,
      'depth'=> $depth
    ];
  }

  function check_regular_expression($struct, $data, $depth = []) {

    [
      'body'=> [
        'pattern'=> $pattern,
        'flag'=> [
          'global'=> $global,
          'case_insensitive'=> $caseInsensitive,
          'multi'=> $multi,
        ]
      ]
    ] = $struct;

    $modifier = '';

    if ($global) $modifier .= 'g';
    if ($caseInsensitive) $modifier .= 'i';
    if ($multi) $modifier .= 'm';

    return [
      'success'=> (bool)preg_match("/$pattern/$modifier", $data),
      'declaration'=> $struct,
      'depth'=> $depth
    ];
  }

  function check_range($struct, $data, $depth = []) {

    [
      'body'=> [
        'keyword'=> $keyword,
        'range'=> [
          'minimal'=> $minimal,
          'maximal'=> $maximal,
        ]
      ]
    ] = $struct;

    // Supports int, double or string primitive type
    // depend on keyword
    if (!(
      ($keyword == PM1_STRING && is_string($data)) ||
      ($keyword == PM1_INT && is_int($data)) ||
      ($keyword == PM1_DOUBLE && is_double($data))
    ))
    {
      return [
        'success'=> false,
        'declaration'=> $struct,
        'depth'=> $depth
      ];
    }

    // compares length if keyword is PM1_STRING in unicode encoding (mb-string provided)
    // ignore whitespace of beginning & end
    if ($keyword == PM1_STRING) {
      $data = mb_strlen(
        trim($data)
      );
    }

    $success =
      ($minimal === null || $minimal <= $data) &&
      ($maximal === null || $maximal >= $data)
    ;

    return [
      'success'=> $success,
      'declaration'=> $struct,
      'depth'=> $depth,
    ];
  }

  function check_datetime($data, array $acceptable) {

    foreach ($acceptable as $v) {

      $instance = DateTime::createFromFormat($v, $data);

      // check if legal
      if ($instance !== false && $instance->format($v) == $data) {
        return true;
      }
    }

    return false;
  }

  function check_value($struct, $data, $depth = []) {

    switch ($struct) {

      case PM1_INT : $success = is_int($data); break;
      case PM1_DOUBLE : $success = is_double($data) || is_int($data); break;
      case PM1_BYTE : $success = is_int($data) && ($data >= 0 && $data <= 255); break;
      case PM1_STRING : $success = is_string($data); break;
      case PM1_BOOL : $success = is_bool($data); break;

      case PM1_DATE: $success = check_datetime($data, ['Y-m-d']); break;
      case PM1_DATETIME: $success = check_datetime($data, ['Y-m-d H:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:sO']); break;
      case PM1_TIME: $success = check_datetime($data, ['H:i:s']); break;

      default: {

        [
          'definition'=> $definition,
        ] = $struct;

        switch ($definition) {

          case PM1_ARRAY : return check_array($struct, $data, $depth);
          case PM1_ENUMERATION : return check_enumeration($struct, $data, $depth);
          case PM1_OBJECT : return check_object($struct, $data, $depth);
          case PM1_REGULAR_EXPRESSION : return check_regular_expression($struct, $data, $depth);
          case PM1_RANGE : return check_range($struct, $data, $depth);

          // This isn't filter exception,
          // because we shouldn't determine real definition of structure,
          // its argument exception
          default: {
            throw new InvalidArgumentException('');
          }
        }
      }
    }

    return [
      'success'=> $success,
      'declaration'=> $struct,
      'depth'=> $depth
    ];
  }

  function filter($notation, $data) {

    [
      'success'=> $success,
      'declaration'=> $declaration,
      'depth'=> $depth,
    ] = check_value(
      $notation,
      $data
    );

    return [
      'success'=> $success,
      'error'=> $success ? null : 'Value of property (or itself) "/'.implode('/', $depth).'" does not fulfill declaration : '.describe($declaration),
      'declaration'=> $declaration,
      'depth'=> $depth
    ];
  }
}
