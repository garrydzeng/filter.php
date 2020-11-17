<?php
namespace GarryDzeng\PM1 {

  define('PM1_INT', 1);
  define('PM1_DOUBLE', 2);
  define('PM1_BOOL', 3);
  define('PM1_STRING', 4);
  define('PM1_BYTE', 5);
  define('PM1_RANGE', 9);
  define('PM1_REGULAR_EXPRESSION', 10);
  define('PM1_OBJECT', 6);
  define('PM1_ENUMERATION', 7);
  define('PM1_ARRAY', 8);

  function TokenStream(string $source) : array {
    return [
      'value'=> $source,
      'token'=> null,
      'index'=> 0
    ];
  }

  function read(array &$stream, $whitespace = false, &$previous = null) : ?string {

    [
      'value'=> &$value,
      'token'=> &$token,
      'index'=> &$index,
    ] = $stream;

    // Non-empty
    while (($token = @$value[ $index++ ]) != null) {

      // previous character whatever its visible character or whitespace
      $previous = @$value[$index - 2];

      // consume a character whatever if toggle on
      // or skip whitespace
      if (
        $whitespace || (
          $token !== "\x20" && // White Space
          $token !== "\x0a" && // New Line
          $token !== "\x0d" && // Carriage Return
          $token !== "\x09"    // Tabs
        )
      )
      {
        return $token;
      }
    }

    return null;
  }

  function as_double(array &$stream) {

    $start = read($stream);

    // Digit
    // Negative Sign
    // Zero
    if (!(
      $start == "\x2d" || // -
      $start == "\x30" || // 0
      $start == "\x31" || // 1
      $start == "\x32" || // 2
      $start == "\x33" || // 3
      $start == "\x34" || // 4
      $start == "\x35" || // 5
      $start == "\x36" || // 6
      $start == "\x37" || // 7
      $start == "\x38" || // 8
      $start == "\x39"    // 9
    ))
    {
      return null;
    }

    // leading zero
    if ('0' == $start && ($token = read($stream)) != null) {

      if ('.' != $token) {
        return [
          'success'=> false,
          'error'=> '
            Illegal double found,
            you should follow a dot character if it starts with zero,
            please check.
          '
        ];
      }

      $start .= $token;
    }

    while (
      ($token = read($stream)) != null && (
        $token == "\x2e" || // .
        $token == "\x30" || // 0
        $token == "\x31" || // 1
        $token == "\x32" || // 2
        $token == "\x33" || // 3
        $token == "\x34" || // 4
        $token == "\x35" || // 5
        $token == "\x36" || // 6
        $token == "\x37" || // 7
        $token == "\x38" || // 8
        $token == "\x39"    // 9
      )
    )
    {
      $start .= $token;
    }

    // Append zero if it ends with dot character (example: "1." => "1.0")
    if ('.' === substr($start, -1)) {
      $start .= '0';
    }

    // convert to language double
    return floatval(
      $start
    );
  }

  function as_int(array &$stream) {

    $start = read($stream);

    if (!(
      $start == "\x2d" || // -
      $start == "\x30" || // 0
      $start == "\x31" || // 1
      $start == "\x32" || // 2
      $start == "\x33" || // 3
      $start == "\x34" || // 4
      $start == "\x35" || // 5
      $start == "\x36" || // 6
      $start == "\x37" || // 7
      $start == "\x38" || // 8
      $start == "\x39"    // 9
    ))
    {
      return null;
    }

    while (
      ($token = read($stream)) != null && (
        $token == "\x30" || // 0
        $token == "\x31" || // 1
        $token == "\x32" || // 2
        $token == "\x33" || // 3
        $token == "\x34" || // 4
        $token == "\x35" || // 5
        $token == "\x36" || // 6
        $token == "\x37" || // 7
        $token == "\x38" || // 8
        $token == "\x39"    // 9
      )
    )
    {
      $start .= $token;
    }

    return intval(
      $start
    );
  }

  function as_name(array &$stream) {

    $start = read($stream);

    /*
     * The first character of the name must be a letter.
     * The underscore is also a legal first character but its use is not recommended at the beginning of a name.
     * Underscore is often used with special commands,
     * and it's sometimes hard to read.
     */
    if (!(
      ($start >= "\x41" && $start <= "\x5a") || // uppercase alpha
      ($start >= "\x61" && $start <= "\x7a") || // lowercase alpha
      ($start == "\x5f") // underscore
    ))
    {
      return null;
    }

    /*
     * Stop if its not (in case-sensitive)
     * letter
     * underscore
     * digit
     */
    while(
      ($token = read($stream)) != null && (
        ($token >= "\x41" && $token <= "\x5a") || // uppercase alpha
        ($token >= "\x61" && $token <= "\x7a") || // lowercase alpha
        ($token >= "\x30" && $token <= "\x39") || // digit
        ($token == "\x5f") // underscore
      )
    )
    {
      $start .= $token;
    }

    return $start;
  }

  function as_keyword(array &$stream, int $keyword) {

    // remaining part of primitive types
    static $remaining = [
      PM1_INT => 'nt',
      PM1_DOUBLE => 'ouble',
      PM1_BOOL => 'ol',
      PM1_STRING => 'tring',
      PM1_BYTE => 'te'
    ];

    $expected = $remaining[$keyword];

    for (
      $index = 0, $length = strlen($expected);
      $index < $length;
      $index ++
    )
    {
      // Simple error
      if (read($stream) != $expected[ $index ]) {
        return [
          'success'=> false,
          'error'=> "
            Invalid primitive type,
            they must be one of int, double, bool, string or byte literal,
            please check!
          "
        ];
      }
    }

    // Some keyword supports define a range
    if (
      $keyword == PM1_STRING ||
      $keyword == PM1_INT ||
      $keyword == PM1_DOUBLE
    )
    {
      $range = as_range($stream, PM1_DOUBLE == $keyword);

      // Parse range successfully
      if ( $range ) {

        [
          'success'=> $success,
          'error'=> $error,
          'range'=> [
            'minimal'=> $minimal,
            'maximal'=> $maximal
          ]
        ] = $range;

        if ($success) {
          return [
            'success'=> $success,
            'definition'=> PM1_RANGE,
            'body'=> [
              'keyword'=> $keyword,
              'range'=> [
                'minimal'=> $minimal,
                'maximal'=> $maximal,
              ]
            ]
          ];
        }

        return [
          'success'=> $success,
          'error'=> $error,
        ];
      }
    }

    return [
      'success'=> true,
      'definition'=> $keyword,
      'body'=> null
    ];
  }

  function as_range(array &$stream, bool $double = false) {

    [
      // create a snapshot of current state
      'value'=> $value,
      'token'=> $token,
      'index'=> $index,
    ] = $stream;

    if ('<' != read($stream)) {

      $stream = [
        'value'=> $value,
        'token'=> $token,
        'index'=> $index,
      ];

      /*
       * don't determine as range
       * if next character doesn't equals to "<" character
       * return special value
       */
      return null;
    }

    $minimal = $double ? as_double($stream) : as_int($stream);

    [
      'token'=> $token,
    ] = $stream;

    // range only contains minimal value if ">" character determined
    if ('>' == $token) {
      return [
        'success'=> true,
        'range'=> [
          'minimal'=> $minimal,
          'maximal'=> null
        ]
      ];
    }

    // Expect "," delimiter before maximal value
    if (',' != $token) {
      return [
        'success'=> false,
        'error'=> '
          Invalid range delimiter,
          You should insert a "," character between minimal & maximal value,
          ignore it when contains minimal value,
          please check!
        '
      ];
    }

    $maximal = $double ? as_double($stream) : as_int($stream);

    [
      'token'=> $token,
    ] = $stream;

    if ('>' != $token) {
      return [
        'success'=> false,
        'error'=> '
          Invalid range closer,
          every range must enclosed by ">" character but got "'.$token.'",
          please check.
        '
      ];
    }

    return [
      'success'=> true,
      'range'=> [
        'minimal'=> $minimal,
        'maximal'=> $maximal
      ]
    ];
  }

  function as_primitive(array &$stream) {

    [
      // Peek a character because we check itself, not children
      // Don't read new character !
      'token'=> $token
    ] = $stream;

    switch ($token) {

      case 'd': return as_keyword($stream, PM1_DOUBLE);
      case 'i': return as_keyword($stream, PM1_INT);
      case 's': return as_keyword($stream, PM1_STRING);
      case 'b': {
        switch (read($stream)) {
          case 'o': return as_keyword($stream, PM1_BOOL);
          case 'y': return as_keyword($stream, PM1_BYTE);
        }
      }
    }

    return [
      'success'=> false,
      'error'=> "
        Invalid primitive type, 
        this is unrecognized character of type's beginning,
        please check.
      "
    ];
  }

  function as_object(array &$stream) {

    $done = [];

    for (;;) {

      $name = as_name($stream);

      [
        'token'=> $token,
      ] = $stream;

      if (!$name) {

        // we determined a t of object so returns
        if ('}' == $token) {
          return [
            'success'=> true,
            'definition'=> PM1_OBJECT,
            'body'=> $done
          ];
        }
        else {
          return [
            'success'=> false,
            'error'=> "
              The first character of the name must be a letter. 
              The underscore is also a legal first character but its use is not recommended at the beginning of a name. 
              Underscore is often used with special commands, 
              and it's sometimes hard to read.
            "
          ];
        }
      }

      $optional = $token == '?';

      // Move to next character if "?" determined
      if ($optional) {
        $token = read($stream);
      }

      // Peek current character
      // it should be colon because it delimits key & value
      if (':' != $token) {
        return [
          'success'=> false,
          'error'=> '
            
          '
        ];
      }

      [
        'success'=> $success,
        'error'=> $error,
        'definition'=> $definition,
        'body'=> $body,
      ] = as_value(
        $stream
      );

      // Stop
      if (!$success) {
        return [
          'success'=> $success,
          'error'=> $error,
        ];
      }

      $done[] = [
        'name'=> $name,
        'is_optional'=> $optional,
        'value'=> [
          'definition'=> $definition,
          'body'=> $body,
        ]
      ];

      // doesn't continue if we don't determine a delimiter
      if (read($stream) != ',') {
        break;
      }
    }

    [
      'token'=> $token
    ] = $stream;

    if ('}' != $token) {
      return [
        'success'=> false,
        'error'=> '
          
        '
      ];
    }

    return [
      'success'=> true,
      'definition'=> PM1_OBJECT,
      'body'=> $done
    ];
  }

  function as_enumeration(array &$stream) {

    $done = [];

    // Enumeration is comma list of (signed or not) numeric
    for (;;) {

      [
        // create a snapshot of current state
        'value'=> $value,
        'token'=> $token,
        'index'=> $index,
      ] = $stream;

      $name = as_name($stream);

      // Name in enumeration used to describes value (optional),
      // its not apart of result
      if ($name) {

        [
          'token'=> $token
        ] = $stream;

        // Peek current character
        // it should be equal because it delimits key & value
        if ('=' != $token) {
          return [
            'success'=> false,
            'error'=> '
              
            '
          ];
        }
      }
      else {
        $stream = [
          'value'=> $value,
          'token'=> $token,
          'index'=> $index,
        ];
      }

      $number = as_int($stream);

      if (!isset($number)) {

        [
          'token'=> $token,
        ] = $stream;

        // we determine a Terminator of enumeration
        if (')' == $token) {
          return [
            'success'=> true,
            'definition'=> PM1_ENUMERATION,
            'body'=> $done
          ];
        }
        else {
          return [
            'success'=> false,
            'error'=> '
              Integer should composed by negative(or positive) sign & digit character, 
              first character must be sign or digit, 
              but exclude zero.
            '
          ];
        }
      }

      $done[] = (int)$number;

      [
        // function as_int() always consume a character even thrown an exception,
        // so peek current character,
        // because it means next character for others.
        'token'=> $delimiter
      ] = $stream;

      // doesn't continue if we don't determine a delimiter
      if ($delimiter != ',') {
        break;
      }
    }

    [
      'token'=> $token
    ] = $stream;

    // enumeration must end with ")" character
    if (')' != $token) {
      return [
        'success'=> false,
        'error'=> '
          
        '
      ];
    }

    return [
      'success'=> true,
      'definition'=> PM1_ENUMERATION,
      'body'=> $done
    ];
  }

  function as_array(array &$stream) {

    $token = read($stream);

    // Every array must contain a primitive, enumeration or object
    if (']' == $token) {
      return [
        'success'=> false,
        'error'=> '
          Empty element found,
          an array must contain a primitive, enumeration or object, 
          please check.
        '
      ];
    }

    $item = ('(' == $token) ? as_enumeration($stream) : ('{' == $token ? as_object($stream) : as_primitive($stream));

    // Array should enclosed by "]" character
    if (']' != read($stream)) {
      return [
        'success'=> false,
        'error'=> '
          
        '
      ];
    }

    return [
      'success'=> true,
      'definition'=> PM1_ARRAY,
      'body'=> $item,
    ];
  }

  function as_regular_expression(array &$stream) {

    $pattern = null;

    while (($token = read($stream, true, $previous)) != null && ('/' != $token || '\\' == $previous)) {
      $pattern .= $token;
    }

    // regular expression must delimited by "/" character
    if ('/' != $token) {
      return [
        'success'=> false,
        'error'=> '
          
        '
      ];
    }

    $global = false;
    $caseInsensitive = false;
    $multi = false;

    // find modifier
    for (;;) {

      [
        'value'=> $value,
        'token'=> $start,
        'index'=> $index,
      ] = $stream;

      // No keyword
      if (($token = read($stream)) === null || (
        $token != 'i' &&
        $token != 'g' &&
        $token != 'm'
      ))
      {
        // restore to previous state if no modifier found
        // ensure we aren't consume anything
        // for others
        $stream = [
          'value'=> $value,
          'token'=> $start,
          'index'=> $index,
        ];

        break;
      }

      switch ($token) {
        case 'g': $global = true; break;
        case 'i': $caseInsensitive = true; break;
        case 'm': $multi = true; break;
      }
    }

    return [
      'success'=> true,
      'definition'=> PM1_REGULAR_EXPRESSION,
      'body'=> [
        'pattern'=> $pattern,
        'flag'=> [
          'global'=> $global,
          'case_insensitive'=> $caseInsensitive,
          'multi'=> $multi
        ]
      ]
    ];
  }

  function as_value(array &$stream) {

    /*
     * Consume a character used to determine parsing policy
     * { -> object
     * ( -> enumeration
     * / -> regular expression
     * [ -> array
     * b -> bool
     * b -> byte
     * d -> double
     * s -> string
     * i -> int
     */
    switch (read($stream)) {

      case '{': return as_object($stream);
      case '(': return as_enumeration($stream);
      case '/': return as_regular_expression($stream);
      case '[': return as_array($stream);

      default: {
        return as_primitive($stream);
      }
    }
  }

  function parse($source) {
    $stream = TokenStream((string)$source);
    return as_value(
      $stream
    );
  }
}
