<?php
namespace PM1 {

  use InvalidArgumentException;

  class ValidateException extends Exception {

  }

  function check_object($struct, $data, $depth = []) : array {

    $success = is_array($data);

    if (!$success) {
      return [
        $success,
        $depth
      ];
    }

    foreach ($struct as [
      'value'=> $struct,
      'is_optional'=> $optional,
      'name'=> $name,
    ])
    {
      $success = false;
      $extra = [];

      [
        // Extract value from target by a name
        $name => $value,
      ] = $data;

      // Execute validation logic if property isn't empty
      // whatever it optional
      if (isset($value)) {
        [0 => $success, 1 => $extra] = check_value($struct, $value, [...$depth, $name]);
      }

      $success = $optional ? null === $value : $success;

      // value of optional property should ignore or be nullable
      if (!$success) {
        return [
          $success,
          $extra
        ];
      }
    }

    return [true, $depth];
  }

  function check_enumeration($struct, $data, $depth = []) {
    return [
      in_array($data, $struct, true),
      $depth
    ];
  }

  function check_array($struct, $data, $depth = []) : array {

    if (!is_array($data)) {
      return [
        false,
        $depth
      ];
    }

    foreach ($data as $index => $value) {

      // Prepare for primitive
      $extra = $depth;

      // Element of array allows primitive, object and enumeration
      switch ($struct) {

        case PM1_INT: $success = is_int($data); break;
        case PM1_DOUBLE: $success = is_double($data); break;
        case PM1_BYTE: $success = is_int($data) && ($data >= 0 && $data <= 255); break;
        case PM1_STRING: $success = is_string($data); break;
        case PM1_BOOL: $success = is_bool($data); break;

        default: {

          [$child, $composed] = $struct;

          switch ($child) {
            case PM1_ENUMERATION: [$success, $extra] = check_enumeration($composed, $value, [...$depth, $index]); break;
            case PM1_OBJECT: [$success, $extra] = check_object($composed, $value, [...$depth, $index]); break;
            default: {
              throw new InvalidArgumentException(

              );
            }
          }
        }
      }

      // Failed to validate if anything
      if (!$success) {
        return [$success, $extra];
      }
    }

    return [true, $depth];
  }

  function check_regular_expression($struct, $data, $depth = []) : array {

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

    return [
      preg_match("/$pattern/$modifier", $data),
      $depth
    ];
  }

  function check_range($struct, $data, $depth = []) : array {

    [
      'lower_bound'=> $minimal,
      'keyword'=> $keyword,
      'upper_bound'=> $maximal,
    ] = $struct;

    // Supports int, double or string primitive type
    // depend on keyword
    if (!(
      ($keyword == PM1_STRING && is_string($data)) ||
      ($keyword == PM1_INT && is_int($data)) ||
      ($keyword == PM1_DOUBLE && is_double($data))
    ))
    {
      return [false, $depth];
    }

    // compares length if keyword is PM1_STRING in unicode encoding (mb-string provided)
    if ($keyword == PM1_STRING) {
      $data = mb_strlen(
        $data
      );
    }

    return [
      ($minimal === null || $minimal <= $data) &&
      ($maximal === null || $maximal >= $data),
      $depth
    ];
  }

  function check_element($struct, $data) {

    switch ($struct) {

      case PM1_INT: return is_int($data);
      case PM1_DOUBLE: return is_double($data);
      case PM1_BYTE: return is_int($data) && ($data >= 0 && $data <= 255);
      case PM1_STRING: return is_string($data);
      case PM1_BOOL: return is_bool($data);

    }

    return false;
  }

  function check_value($struct, $data, $depth = []) : array {

    switch ($struct) {

      case PM1_INT: return [is_int($data), $depth];
      case PM1_DOUBLE: return [is_double($data), $depth];
      case PM1_BYTE: return [is_int($data) && ($data >= 0 && $data <= 255), $depth];
      case PM1_STRING: return [is_string($data), $depth];
      case PM1_BOOL: return [is_bool($data), $depth];

      default: {

        [$struct, $composed] = $struct;

        switch ($struct) {

          case PM1_ARRAY: return check_array($composed, $data, $depth);
          case PM1_ENUMERATION: return check_enumeration($composed, $data, $depth);
          case PM1_OBJECT: return check_object($composed, $data, $depth);
          case PM1_REGULAR_EXPRESSION: return check_regular_expression($composed, $data, $depth);
          case PM1_RANGE: return check_range($composed, $data, $depth);

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

  function filter($struct, $data) {

    [
      $isSuccess,
      $depth
    ] = check_value(
      $struct,
      $data
    );

    if (!$isSuccess) {
      throw new ValidateException('Invalid value for '.implode('.', $depth));
    }
  }
}