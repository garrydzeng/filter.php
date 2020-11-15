<?php
namespace GarryDzeng\Filter {

  class FilterTest extends \PHPUnit\Framework\TestCase {

    public function testCaching() {

      $self = new Filter();

      $self->filter('./notation/user.pm1', [
        'id'=> 1,
        'openid'=> '8945506e3e6347a0937c53833bca2cdf',
        'sn'=> 1,
        'type'=> 6,
        'plan'=> [
          'space'=> 2, // Invalid
          'private_repos'=> 20,
          'name'=> 'Small'
        ]
      ]);

    }
  }
}