<?php
namespace GarryDzeng\PM1 {

  class DescribeTest extends \PHPUnit\Framework\TestCase {

    public function testDescribe() {

      $this->assertEquals('date', describe(PM1_DATE));
      $this->assertEquals('datetime', describe(PM1_DATETIME));
      $this->assertEquals('time', describe(PM1_TIME));

      $this->assertEquals('int', describe(PM1_INT));
      $this->assertEquals('double', describe(PM1_DOUBLE));
      $this->assertEquals('bool', describe(PM1_BOOL));
      $this->assertEquals('string', describe(PM1_STRING));
      $this->assertEquals('byte', describe(PM1_BYTE));

      $this->assertEquals('int', describe(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_INT,'range'=> ['minimal'=> null,'maximal'=> null]]]));
      $this->assertEquals('int<200,20000>', describe(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_INT,'range'=> ['minimal'=> 200,'maximal'=> 20000]]]));
      $this->assertEquals('int<,100>', describe(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_INT,'range'=> ['minimal'=> null,'maximal'=> 100]]]));
      $this->assertEquals('int<300>', describe(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_INT,'range'=> ['minimal'=> 300,'maximal'=> null]]]));

      $this->assertEquals('/^s: \\\d+/i', describe(['definition'=> PM1_REGULAR_EXPRESSION,'body'=> ['pattern'=> '^s: \\\d+','flag'=> ['global'=> false,'case_insensitive'=> true,'multi'=> false]]]));

      $this->assertEquals('{id:int,account_balance?:double}', describe(['definition'=> PM1_OBJECT,'body'=> [['name'=> 'id','is_optional'=> false,'value'=> PM1_INT],['name'=> 'account_balance','is_optional'=> true,'value'=> PM1_DOUBLE]]]));
      $this->assertEquals('(1,0)', describe(['definition'=> PM1_ENUMERATION,'body'=> [1,0]]));
      $this->assertEquals('[{id:int}]', describe(['definition'=> PM1_ARRAY,'body'=> ['definition'=> PM1_OBJECT,'body'=> [['name'=> 'id','is_optional'=> false,'value'=> PM1_INT]]]]));
      $this->assertEquals('[int]', describe(['definition'=> PM1_ARRAY,'body'=> PM1_INT]));

      $this->assertEquals('{}', describe(['definition'=> PM1_OBJECT,'body'=> []]));
      $this->assertEquals('()', describe(['definition'=> PM1_ENUMERATION,'body'=> []]));
      $this->assertEquals('[]', describe(['definition'=> PM1_ARRAY,'body'=> null]));
    }
  }
}