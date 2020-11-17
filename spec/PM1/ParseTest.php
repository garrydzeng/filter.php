<?php
namespace GarryDzeng\PM1 {

  class ParseTest extends \PHPUnit\Framework\TestCase {

    public function testOffset() {

      ['error'=> $error] = parse('int<a,');

      var_dump($error);
    }

    public function testParse() {

      $source = <<<Source
        {
          id: int<1>,
          openid: /^[\da-f]{32}$/igm,
          sn: int,
          describe_as?: [string],
          plan: {
             space: int<1>,
             private_repos: int<0>,
             name: string,
          },
          type: (
            company = 1,
            business_unit = 2,
            unit = 3,
            center = 4,
            team = 5,
            6,
          )
        }
        Source
      ;

      $struct = parse($source);

      var_dump($struct);
    }
  }
}