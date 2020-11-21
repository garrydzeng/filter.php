<?php
namespace GarryDzeng\PM1 {

  use InvalidArgumentException;

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
  define('PM1_DATE', 11);
  define('PM1_DATETIME', 12);
  define('PM1_TIME', 13);

  function fault($index) {
    throw new InvalidArgumentException(<<< Message
      You have an error in your PM1 syntax, 
      check the manual that corresponds to your library version for the right syntax to use, 
      near offset <$index>.
      Message
    );
  }

  function TokenStream(string $source) {
    return [
      'value'=> $source,
      'token'=> null,
      'index'=> 0
    ];
  }

  function read(array &$stream, $whitespace = false, &$previous = null) {

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
    if ('0' == $start && read($stream) != null) {

      [
        'token'=> $token,
        'index'=> $index,
      ] = $stream;

      // leading zero should follow a dot character not another number
      if ('.' != $token) {
        fault(
          $index - 1
        );
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

  function as_keyword(array &$stream, $keyword) {

    // remaining part of primitive types
    static $remaining = [
      PM1_DATE => 'te',
      PM1_DATETIME => 'tetime',
      PM1_TIME => 'ime',
      PM1_INT => 'nt',
      PM1_DOUBLE => 'uble',
      PM1_BOOL => 'ol',
      PM1_STRING => 'tring',
      PM1_BYTE => 'te'
    ];

    $expected = $remaining[$keyword];

    [
      'index'=> $start,
    ] = $stream;

    for (
      $index = 0, $length = strlen($expected);
      $index < $length;
      $index ++
    )
    {
      if ($expected[ $index ] != read($stream)) {
        fault(
          ($start + $index) - 1
        );
      }
    }

    // Some keyword supports define a range
    if (
      $keyword == PM1_STRING ||
      $keyword == PM1_INT ||
      $keyword == PM1_DOUBLE
    )
    {
      [
        'minimal'=> $minimal,
        'maximal'=> $maximal,
      ] = as_range($stream, PM1_DOUBLE == $keyword);

      // Parse range successfully
      // don't determined as range if minimal & maximal value are empty both
      // just use keyword
      if (
        isset($minimal) ||
        isset($maximal)
      )
      {
        return [
          'definition'=> PM1_RANGE,
          'body'=>  [
            'keyword'=> $keyword,
            'range'=> [
              'minimal'=> $minimal,
              'maximal'=> $maximal,
            ]
          ]
        ];
      }
    }

    return $keyword;
  }

  function as_range(array &$stream, $double = false) {

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
      'index'=> $index,
    ] = $stream;

    // range only contains minimal value if ">" character determined
    if ('>' == $token) {
      return [
        'minimal'=> $minimal,
        'maximal'=> null
      ];
    }

    // Expect "," delimiter before maximal value
    if (',' != $token) {
      fault(
        $index
      );
    }

    $maximal = $double ? as_double($stream) : as_int($stream);

    [
      'token'=> $token,
      'index'=> $index,
    ] = $stream;

    if ('>' != $token) {
      fault(
        $index
      );
    }

    return [
      'minimal'=> $minimal,
      'maximal'=> $maximal
    ];
  }

  function as_primitive(array &$stream) {

    [
      // Peek a character because we check itself, not children
      // Don't read new character !
      'token'=> $token,
      'index'=> $index,
      'value'=> $value,
    ] = $stream;

    switch ($token) {

      case 'i': return as_keyword($stream, PM1_INT);
      case 's': return as_keyword($stream, PM1_STRING);
      case 't': return as_keyword($stream, PM1_TIME);
      case 'd': {
        switch (read($stream)) {
          case 'a': return as_keyword($stream, substr($value, $index, 7) == 'atetime' ? PM1_DATETIME : PM1_DATE);
          case 'o': return as_keyword($stream, PM1_DOUBLE);
        }
      }
      case 'b': {
        switch (read($stream)) {
          case 'o': return as_keyword($stream, PM1_BOOL);
          case 'y': return as_keyword($stream, PM1_BYTE);
        }
      }
    }

    fault($index - 1);
  }

  function as_object(array &$stream) {

    $done = [];

    for (;;) {

      $name = as_name($stream);

      [
        'token'=> $token,
        'index'=> $index,
      ] = $stream;

      if (!$name) {

        // we determined a t of object so returns
        if ('}' == $token) {
          return [
            'definition'=> PM1_OBJECT,
            'body'=> $done
          ];
        }

        fault($index - 1);
      }

      $optional = $token == '?';

      // Move to next character if "?" determined
      if ($optional) {
        read(
          $stream
        );
      }

      [
        'token'=> $token,
        'index'=> $index,
      ] = $stream;

      // Peek current character
      // it should be colon because it delimits key & value
      if (':' != $token) {
        fault(
          $index - 1
        );
      }

      $done[] = [
        'name'=> $name,
        'is_optional'=> $optional,
        'value'=> as_value(
          $stream
        )
      ];

      // doesn't continue if we don't determine a delimiter
      if (read($stream) != ',') {
        break;
      }
    }

    [
      'token'=> $token,
      'index'=> $index,
    ] = $stream;

    if ('}' != $token) {
      fault(
        $index
      );
    }

    return [
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
          'token'=> $token,
          'index'=> $start,
        ] = $stream;

        // Peek current character
        // it should be equal because it delimits key & value
        if ('=' != $token) {
          fault(
            $start - 1
          );
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
          'index'=> $index,
        ] = $stream;

        // we determine a Terminator of enumeration
        if (')' == $token) {
          return [
            'definition'=> PM1_ENUMERATION,
            'body'=> $done
          ];
        }

        fault($index - 1);
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
      'token'=> $token,
      'index'=> $index,
    ] = $stream;

    // enumeration must end with ")" character
    if (')' != $token) {
      fault(
        $index
      );
    }

    return [
      'definition'=> PM1_ENUMERATION,
      'body'=> $done
    ];
  }

  function as_array(array &$stream) {

    read($stream);

    [
      'token'=> $token,
      'index'=> $index,
    ] = $stream;

    // Every array must contain a primitive, enumeration or object
    if (']' == $token) {
      return [
        'definition'=> PM1_ARRAY,
        'body'=> []
      ];
    }

    $item = ('(' == $token) ? as_enumeration($stream) : ('{' == $token ? as_object($stream) : as_primitive($stream));

    read($stream);

    [
      'token'=> $token,
      'index'=> $index,
    ] = $stream;

    // Array should enclosed by "]" character
    if (']' != $token) {
      fault(
        $index - 1
      );
    }

    return [
      'definition'=> PM1_ARRAY,
      'body'=> $item
    ];
  }

  function as_regular_expression(array &$stream) {

    $pattern = null;

    //
    while (($token = read($stream, true, $previous)) != null && ('/' != $token || '\\' == $previous)) {
      $pattern .= $token;
    }

    // regular expression must delimited by "/" character
    if ('/' != $token) {

      [
        'index'=> $index,
      ] = $stream;

      fault($index - 1);
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
