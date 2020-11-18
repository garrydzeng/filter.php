<?php
namespace GarryDzeng\PM1 {

  use DateTime;

  function check_object($struct, $data, $depth = []) : array {

    // Expect associative array in PHP language
    if (!is_array($data)) {
      return [
        'success'=> false,
        'declaration'=> $struct,
        'depth'=> $depth
      ];
    }

    [
      'body'=> $body,
    ] = $struct;

    foreach ($body as [
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

  function check_array($struct, $data, $depth = []) : array {

    if (!is_array($data)) {
      return [
        'success'=> false,
        'declaration'=> $struct,
        'depth'=> $depth
      ];
    }

    [
      'body'=> $body,
    ] = $struct;

    foreach ($data as $index => $value) {

      // Prepare for primitive
      $extra = $depth;

      // Element of array allows primitive, object and enumeration
      switch ($body) {

        case PM1_INT: $success = is_int($data); break;
        case PM1_DOUBLE: $success = is_double($data); break;
        case PM1_BYTE: $success = is_int($data) && ($data >= 0 && $data <= 255); break;
        case PM1_STRING: $success = is_string($data); break;
        case PM1_BOOL: $success = is_bool($data); break;

        default: {

          [
            'definition'=> $what,
          ] = $body;

          switch ($what) {

            case PM1_ENUMERATION: $future = check_enumeration($body, $value, [...$depth, $index]); break;
            case PM1_OBJECT: $future = check_object($body, $value, [...$depth, $index]); break;

            default: {
              // unknown definition found
              return [
                'success'=> false,
                'declaration'=> $body ?? [],
                'depth'=> $extra
              ];
            }
          }

          [
            // Expand result from validation calling
            'success'=> $success,
            'declaration'=> $declaration,
            'depth'=> $extra
          ] = $future;
        }
      }

      if (!$success) {
        return [
          'success'=> false,
          'declaration'=> $declaration ?? [],
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

  function check_regular_expression($struct, $data, $depth = []) : array {

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

  function check_range($struct, $data, $depth = []) : array {

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
    if ($keyword == PM1_STRING) {
      $data = mb_strlen(
        $data
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

  function check_datetime($struct, $data, $depth = []) {

    [
      'definition'=> $what,
    ] = $struct;


    switch ($what) {
      case PM1_DATE: $standard = 'Y-m-d'; break;
      case PM1_DATETIME: $standard = 'Y-m-d H:i:s'; break;
      case PM1_TIME: $standard = 'H:i:s'; break;
    }

    if (isset( $standard )) {

      $instance = DateTime::createFromFormat(
        $standard,
        $data
      );

      return [
        'success'=> false !== $instance && $instance->format($standard) == $data,
        'declaration'=> $struct,
        'depth'=> $depth,
      ];
    }

    return [
      'success'=> false,
      'declaration'=> $struct,
      'depth'=> $depth,
    ];
  }

  function check_value($struct, $data, $depth = []) : array {

    [
      'definition'=> $definition,
    ] = $struct;

    switch ($definition) {

      case PM1_ARRAY : return check_array($struct, $data, $depth);
      case PM1_ENUMERATION : return check_enumeration($struct, $data, $depth);
      case PM1_OBJECT : return check_object($struct, $data, $depth);
      case PM1_REGULAR_EXPRESSION : return check_regular_expression($struct, $data, $depth);
      case PM1_RANGE : return check_range($struct, $data, $depth);

      case PM1_DATE:
      case PM1_DATETIME:
      case PM1_TIME:
        return check_datetime($struct, $data, $depth);

      case PM1_INT : $success = is_int($data); break;
      case PM1_DOUBLE : $success = is_double($data); break;
      case PM1_BYTE : $success = is_int($data) && ($data >= 0 && $data <= 255); break;
      case PM1_STRING : $success = is_string($data); break;
      case PM1_BOOL : $success = is_bool($data); break;

      default: {
        $success = false;
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
      'depth'=> $depth
    ] = check_value(
      $notation,
      $data
    );

    $error = 'Everything is OK';

    if (!$success) {
      $error = 'Value of property (or itself) "/'.implode('/', $depth).'" does not fulfill declaration : '.describe($declaration);
    }

    return [
      'success'=> $success,
      'error'=> $error,
      'declaration'=> $declaration,
      'depth'=> $depth
    ];
  }
}