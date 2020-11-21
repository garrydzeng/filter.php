<?php
namespace GarryDzeng\PM1 {

  class ParseTest extends \PHPUnit\Framework\TestCase {

    public function testParse() {

      $this->assertEquals(PM1_DATE, parse('date'));
      $this->assertEquals(PM1_DATETIME, parse('datetime'));
      $this->assertEquals(PM1_TIME, parse('time'));

      $this->assertEquals(PM1_INT, parse('int'));
      $this->assertEquals(PM1_INT, parse('int<,>'));
      $this->assertEquals(PM1_DOUBLE, parse('double'));
      $this->assertEquals(PM1_DOUBLE, parse('double<,>'));
      $this->assertEquals(PM1_BOOL, parse('bool'));
      $this->assertEquals(PM1_STRING, parse('string'));
      $this->assertEquals(PM1_STRING, parse('string<,>'));
      $this->assertEquals(PM1_BYTE, parse('byte'));

      $this->assertEquals(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_INT,'range'=> ['minimal'=> 1,'maximal'=> null]]], parse('int<1>'));
      $this->assertEquals(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_INT,'range'=> ['minimal'=> 1,'maximal'=> 9]]], parse('int<1,9>'));
      $this->assertEquals(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_INT,'range'=> ['minimal'=> null,'maximal'=> 9]]], parse('int<,9>'));

      $this->assertEquals(['definition'=> PM1_REGULAR_EXPRESSION,'body'=> ['pattern'=> '^s: \\\d+','flag'=> ['global'=> false,'case_insensitive'=> true,'multi'=> false]]], parse('/^s: \\\d+/i'));

      $this->assertEquals(['definition'=> PM1_OBJECT,'body'=> []], parse('{}'));
      $this->assertEquals(['definition'=> PM1_ENUMERATION, 'body'=> []], parse('()'));
      $this->assertEquals(['definition'=> PM1_ARRAY,'body'=> []], parse('[]'));

      $this->assertEquals(['definition'=> PM1_OBJECT,'body'=> [['name'=> 'id','is_optional'=> false,'value'=> PM1_INT],['name'=> 'account_balance','is_optional'=> true,'value'=> PM1_DOUBLE]]], parse('{id: int,account_balance?: double,}'));
      $this->assertEquals(['definition'=> PM1_ENUMERATION,'body'=> [1,0]], parse('(one_piece=1,0)'));
      $this->assertEquals(['definition'=> PM1_ARRAY,'body'=> ['definition'=> PM1_OBJECT,'body'=> [['name'=> 'id','is_optional'=> false,'value'=> PM1_INT]]]], parse('[{id:int}]'));
      $this->assertEquals(['definition'=> PM1_ARRAY,'body'=> PM1_INT], parse('[int]'));
    }
  }
}