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

  function ReadableStream($source) {
    return [
      'value'=> $source,
      'token'=> null,
      'index'=> -1
    ];
  }

  function clear($source) {

    $range = [];

    for (
      $index = 0, $length = strlen($source);
      $index < $length;
      $index ++
    )
    {
      // First character of line or block comment are backslash both
      if ($source[ $index ] === '/') {

        $couples = $source[$index + 1];

        // Start a comment
        if ($couples === '/' || $couples === '*') {

          $range[] = $index;

          // Determine as block comment if third character equals to * character
          $isBlockComment = $couples === '*';
          $done = false;

          // Go ahead
          while ($index++ < $length) {

            // Test with difference terminator
            if ($isBlockComment ? ($source[$index] === '*' && $source[$index + 1] === '/') : $source[$index] === "\n") {
              $done = true;
              $range[] = $index + ($isBlockComment ? 2 : 1);
              break;
            }
          }

          if (!$done) {
            throw new InvalidArgumentException(

            );
          }
        }
      }
    }

    if (!$range) {
      return $source;
    }

    $range = [0, ...$range, $length];
    $apart = '';

    for (
      $index = 0, $length = count($range);
      $index < $length;
      $index += 2
    )
    {
      $apart .= substr($source, $range[$index], $range[$index + 1] - $range[$index]);
    }

    return $apart;
  }

  function previous(array $stream, $whitespace = true) {

    [
      'value'=> $value,
      'index'=> $index,
    ] = $stream;

    while (($token = $value[ --$index ]) !== '') {

      if (
        $whitespace || (
          $token !== "\x20" && // White Space
          $token !== "\x0a" && // New Line
          $token !== "\x0d" && // Carriage Return
          $token !== "\x09"    // Tabs
        ))
      {
        return $token;
      }
    }

    return null;
  }

  function read(array &$stream, $whitespace = false, $start = -1) {

    [
      'value'=> &$value,
      'token'=> &$token,
      'index'=> &$index,
    ] = $stream;

    if ($start !== -1) {
      $index = $start;
    }

    while (($token = $value[ ++$index ]) !== '') {

      if (
        $whitespace || (
          $token !== "\x20" && // White Space
          $token !== "\x0a" && // New Line
          $token !== "\x0d" && // Carriage Return
          $token !== "\x09"    // Tabs
        ))
      {
        return $token;
      }
    }

    return null;
  }

  function as_double(array &$stream, $start = -1) {

    $start = read(
      $stream,
      false,
      $start
    );

    // Digit
    // Negative Sign
    // Zero
    if ($start != "\x2d" && ($start < "\x30" || $start > "\x39")) {
      return null;
    }

    $index = 0;

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
      // Leading zero should follow a dot character not another number or zero ...
      //
      if (
        $start[ $index ] == '0' &&
        $token != '.'
      )
      {
        [
          // The index of unexpected character
          'index'=> $current,
        ] = $stream;

        throw new InvalidArgumentException(<<<Message
          You have an error in your PM1 syntax, 
          check the manual that corresponds to your library version for the right syntax to use, 
          near offset <$current>.
          Message
        );
      }

      $start .= $token;
      $index ++;
    }

    // Append zero if it ends with dot character (example: "1." => "1.0")
    if ('.' === substr($start, -1)) {
      $start .= '0';
    }

    return (double)$start;
  }

  function as_int(array &$stream, $start = -1) {

    $start = read(
      $stream,
      false,
      $start
    );

    if ($start != "\x2d" && ($start < "\x30" || $start > "\x39")) {
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
//      if ($start === '0' || ($start == '-')) {
//
//      }

      $start .= $token;
    }

    return (int)$start;
  }

  function as_keyword(array &$stream, $keyword, $point = null, $next = 0) {

    // remaining part of primitive types
    static $remaining = [
      PM1_DATE => 'te',
      PM1_DATETIME => 'time',
      PM1_TIME => 'ime',
      PM1_INT => 'nt',
      PM1_DOUBLE => 'uble',
      PM1_BOOL => 'ol',
      PM1_STRING => 'tring',
      PM1_BYTE => 'te'
    ];

    $apart = $remaining[$keyword];

    // Accept empty string or remaining characters ...
    if ($apart) {

      for (
        $index = 0, $length = strlen($apart);
        $index < $length;
        $index ++
      )
      {
        if ($apart[ $index ] != read($stream)) {

          [
            // Replace variable to index of unexpected character
            'index'=> $index,
          ] = $stream;

          throw new InvalidArgumentException(<<<Message
            You have an error in your PM1 syntax, 
            check the manual that corresponds to your library version for the right syntax to use, 
            near offset <$index>.
            Message
          );
        }
      }
    }

    [
      'value'=> $value,
      'index'=> $i,
    ] = $stream;

    /*
     * Compares key character of state machine (maybe null) with next character ...
     * as keyword if they are equals
     */
    if ($value[++$i] === $point) {
      return as_keyword(
        $stream,
        $next
      );
    }

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
      'value'=> $value,
      'index'=> $index,
    ] = $stream;

    /*
     * don't determine as range
     * if next character doesn't equals to "<" character
     * return special value
     */
    if ($value[++$index] != '<') {
      return null;
    }

    $minimal = $double ? as_double($stream, $index) : as_int($stream, $index);
    $maximal = null;

    [
      'token'=> $token,
    ] = $stream;

    // Expect "," delimiter before maximal value
    if (',' == $token) {
      $maximal = $double ? as_double($stream) : as_int($stream);
    }

    [
      'token'=> $token,
      'index'=> $index,
    ] = $stream;

    // range should enclosed by ">" character at the end
    if ('>' != $token) {
      throw new InvalidArgumentException( <<<Message
        You have an error in your PM1 syntax, 
        check the manual that corresponds to your library version for the right syntax to use, 
        near offset <$index>.
        Message
      );
    }

    return [
      'minimal'=> $minimal,
      'maximal'=> $maximal
    ];
  }

  function as_primitive(array &$stream) {

    [
      // Consumed character is part of primitive type so peek a character instead of read
      // assign to a variable
      'token'=> $token,
    ] = $stream;

    switch ($token) {

      case 't': return as_keyword($stream, PM1_TIME);
      case 's': return as_keyword($stream, PM1_STRING);
      case 'i': return as_keyword($stream, PM1_INT);
      case 'd': {
        switch (read($stream)) {
          case 'a': return as_keyword($stream, PM1_DATE, 't', PM1_DATETIME); // Should be date or datetime if it starts with "da"
          case 'o': return as_keyword($stream, PM1_DOUBLE);
        }
      }
      case 'b': {
        switch (read($stream)) {
          case 'o': return as_keyword($stream, PM1_BOOL);
          case 'y': return as_keyword($stream, PM1_BYTE);
        }
      }

      default: {

        [
          // The index of unexpected character
          'index'=> $index
        ] = $stream;

        throw new InvalidArgumentException(<<<Message
          You have an error in your PM1 syntax, 
          check the manual that corresponds to your library version for the right syntax to use, 
          near offset <$index>.
          Message
        );
      }
    }
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

  function as_object(array &$stream) {

    $body = [];

    // Parse a pair
    for (;;) {

      $name = as_name($stream);

      [
        'token'=> $token,
        'index'=> $index,
      ] = $stream;

      // Pair should starts with a name
      if (!$name) {

        // we determined a t of object so break out
        if ('}' == $token) {
          break;
        }

        throw new InvalidArgumentException(<<<Message
          You have an error in your PM1 syntax, 
          check the manual that corresponds to your library version for the right syntax to use, 
          near offset <$index>.
          Message
        );
      }

      $optional = $token == '?';

      // Move to next character if "?" determined
      if ($optional) {
        read(
          $stream,
          false
        );
      }

      [
        'token'=> $token,
        'index'=> $index,
      ] = $stream;

      if ("\x3a" != $token) {
        throw new InvalidArgumentException(<<<Message
          You have an error in your PM1 syntax, 
          check the manual that corresponds to your library version for the right syntax to use, 
          near offset <$index>.
          Message
        );
      }

      $body[] = [
        'name'=> $name,
        'is_optional'=> $optional,
        'value'=> as_value(
          $stream
        )
      ];

      // doesn't continue if we don't determine a delimiter
      if ("\x2c" != read($stream)) {
        break;
      }
    }

    [
      'token'=> $token,
      'index'=> $index,
    ] = $stream;

    // Object should enclosed '}' at the end
    if ('}' != $token) {
      throw new InvalidArgumentException(<<<Message
        You have an error in your PM1 syntax, 
        check the manual that corresponds to your library version for the right syntax to use, 
        near offset <$index>.
        Message
      );
    }

    return [
      'definition'=> PM1_OBJECT,
      'body'=> $body
    ];
  }

  function as_enumeration(array &$stream) {

    $body = [];

    // Enumeration is comma list of (signed or not) numeric
    for (;;) {

      $name = as_name($stream);

      [
        'token'=> $token,
        'index'=> $index,
      ] = $stream;

      // Name in enumeration used to describes value (optional),
      // its not apart of result
      if ($name) {

        // Internal point will references to first character after name!!!
        // expect pair delimiter "="
        if ('=' === $token) {
          $index++;
        }
        else {
          throw new InvalidArgumentException( <<<Message
            You have an error in your PM1 syntax, 
            check the manual that corresponds to your library version for the right syntax to use, 
            near offset <$index>.
            Message
          );
        }
      }

      $number = as_int($stream, --$index);

      [
        'token'=> $token,
        'index'=> $index,
      ] = $stream;

      // Member isn't a integer number or enumeration ends with "," character
      if (null === $number) {

        // we determine a Terminator of enumeration
        if (')' === $token) {
          break;
        }

        throw new InvalidArgumentException( <<<Message
          You have an error in your PM1 syntax, 
          check the manual that corresponds to your library version for the right syntax to use, 
          near offset <$index>.
          Message
        );
      }

      $body[] = $number;

      // don't continue if we don't determine a delimiter
      if ("\x2c" != $token) {
        break;
      }
    }

    [
      'token'=> $token,
      'index'=> $index,
    ] = $stream;

    if (')' != $token) {
      throw new InvalidArgumentException(<<<Message
        You have an error in your PM1 syntax, 
        check the manual that corresponds to your library version for the right syntax to use, 
        near offset <$index>.
        Message
      );
    }

    return [
      'definition'=> PM1_ENUMERATION,
      'body'=> $body
    ];
  }

  function as_array(array &$stream) {

    $token = read($stream);
    $value = null;

    // Every array must contain a primitive, enumeration or object
    if ($token !== ']') {
      $value = ($token === '(') ? as_enumeration($stream) : ($token === '{' ? as_object($stream) : as_primitive($stream));
      $token = read($stream);
    }

    // Not enclosed by "]" character
    if ($token !== ']') {

      [
        // The index of unexpected character
        'index'=> $index,
      ] = $stream;

      throw new InvalidArgumentException( <<<Message
        You have an error in your PM1 syntax, 
        check the manual that corresponds to your library version for the right syntax to use, 
        near offset <$index>.
        Message
      );
    }

    return [
      'definition'=> PM1_ARRAY,
      'body'=> $value
    ];
  }

  function as_regular_expression(array &$stream) {

    $pattern = null;

    // Not Terminated & delimiter (no escaped)
    while (($token = read($stream, true)) !== null && ($token != '/' || previous($stream) == '\\')) {
      $pattern .= $token;
    }

    // Regular expression must delimited by "/" character or
    // empty pattern found
    if (
      $pattern === null ||
      $token !== '/'
    )
    {
      [
        // The index of unexpected character
        'index'=> $index,
      ] = $stream;

      throw new InvalidArgumentException(<<<Message
        You have an error in your PM1 syntax, 
        check the manual that corresponds to your library version for the right syntax to use, 
        near offset <$index>.
        Message
      );
    }

    $global = false;
    $caseInsensitive = false;
    $multi = false;

    // find modifier
    for (;;) {

      $insurance = $stream;

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
        $stream = $insurance;
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
     * Consume a character used to determine parsing policy (ignore any whitespace before we meet a keyword)
     * { -> object
     * ( -> enumeration
     * / -> regular expression
     * ; -> single comment
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
    $stream = ReadableStream(clear((string)$source));
    return as_value(
      $stream
    );
  }
}
