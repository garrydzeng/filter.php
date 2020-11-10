<?php
namespace GarryDzeng\Filter {

  class FilterTest extends \PHPUnit\Framework\TestCase {

    public function testCaching() {

      $self = new Filter();

      $self->filter('./notation/user.pm1', []);

    }
  }
}