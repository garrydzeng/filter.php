<?php
namespace GarryDzeng\PM1 {

  use InvalidArgumentException;
  use Throwable;

  function print_object($struct) {

    $pieces = [];

    foreach ($struct as [
      'value'=> $value,
      'is_optional'=> $optional,
      'name'=> $name,
    ])
    {
      $pieces[] = ($optional ? "$name?: " : "$name: ").stringify($value);
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
      'bound'=> [
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

    return isset($maximal) ? (isset($minimal) ? "$keyword<$minimal>" : $keyword) : "$keyword<$minimal,$maximal>";
  }

  function stringify($struct) {

    switch ($struct) {

      case PM1_INT: return 'int';
      case PM1_DOUBLE: return 'double';
      case PM1_BYTE: return 'byte';
      case PM1_STRING: return 'string';
      case PM1_BOOL: return 'bool';

      default: {

        [
          'definition'=> $what,
          'body'=> $body,
        ] = $struct;

        switch ($what) {

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
  }

  class ValidateException extends Exception {

    public $declaration;
    public $depth;

    public function __construct($message, $declaration, array $depth, Throwable $previous = null) {

      $this->declaration = $declaration;
      $this->depth = $depth;

      parent::__construct(
        $message,
        0x00,
        $previous
      );
    }
  }

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
              throw new InvalidArgumentException(

              );
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
        'bound'=> [
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

  function check_value($struct, $data, $depth = []) : array {

    switch ($struct) {

      case PM1_INT: $success = is_int($data); break;
      case PM1_DOUBLE: $success = is_double($data); break;
      case PM1_BYTE: $success = is_int($data) && ($data >= 0 && $data <= 255); break;
      case PM1_STRING: $success = is_string($data); break;
      case PM1_BOOL: $success = is_bool($data); break;

      default: {

        [
          'definition'=> $what,
        ] = $struct;

        switch ($what) {

          case PM1_ARRAY: return check_array($struct, $data, $depth);
          case PM1_ENUMERATION: return check_enumeration($struct, $data, $depth);
          case PM1_OBJECT: return check_object($struct, $data, $depth);
          case PM1_REGULAR_EXPRESSION: return check_regular_expression($struct, $data, $depth);
          case PM1_RANGE: return check_range($struct, $data, $depth);

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

    // Handles primitive type
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

    if (!$success) {
      throw new ValidateException(
        'Value of property (or itself) "/'.implode('/', $depth).'" does not fulfill declaration : '.stringify($declaration),
        $declaration,
        $depth
      );
    }
  }
}