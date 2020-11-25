<?php
namespace GarryDzeng\PM1 {

  class FilterTest extends \PHPUnit\Framework\TestCase {

    public function testBadPrimitiveArray() {
      $this->assertEquals(
        [
          'success'=> false,
          'error'=> 'Value of property (or itself) "/" does not fulfill declaration : [int]',
          'declaration'=> [
            'definition'=> PM1_ARRAY,
            'body'=> PM1_INT
          ],
          'depth'=> []
        ],
        filter(
          ['definition'=> PM1_ARRAY,'body'=> PM1_INT],
          ["string"]
        )
      );
    }
  }
}