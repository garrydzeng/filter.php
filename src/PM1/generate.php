<?php
namespace GarryDzeng\PM1 {

  use InvalidArgumentException;

  function mock_object($struct) {



  }

  function mock_enumeration($struct) {
    return array_rand(
      $struct
    );
  }

  function mock_array() {

  }

  function mock_regular_expression($struct) {



  }

  function mock_range() {}

  function mock_byte() {}

  function mock_string() {


  }

  function mock_bool() { return lcg_value() > 0.5; }

  function mock_double() {}

  function mock_value($struct) {

    [
      'definition'=> $definition,
      'body'=> $body,
    ] = $struct;

    switch ($definition) {

      case PM1_INT: return random_int(PHP_INT_MIN, PHP_INT_MAX);
      case PM1_DOUBLE: return 1.2;
      case PM1_BYTE: return random_bytes(1);
      case PM1_STRING: return '';
      case PM1_BOOL: return lcg_value() > 0.5;



      default: {

        [
          'definition'=> $what,
          'body'=> $body,
        ] = $struct;

        switch ($what) {

          default: {
            throw new InvalidArgumentException(

            );
          }
        }
      }
    }
  }

  function generate($struct) {
    return [];
  }
}