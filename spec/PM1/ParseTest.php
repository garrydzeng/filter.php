<?php
namespace GarryDzeng\PM1 {

  use InvalidArgumentException;

  class ParseTest extends \PHPUnit\Framework\TestCase {

    public function testParseEmptyObject() {
      $this->expectException(InvalidArgumentException::class);
      parse('{}');
    }

    public function testParseZeroFollowsDotCharacter() {
      $this->expectException(InvalidArgumentException::class);
      parse('double<-01.1>');
    }

    public function testCleanComment() {
      $this->assertEquals("\r\n{\r\n  id: int<1>,\r\n  openid: /^[\da-f]{32}$/im,\r\n}", clear("/*\r\n* main definition\r\n */\r\n{\r\n  id: int<1>,\r\n  // means identifier\r\nopenid: /^[\da-f]{32}$/im,\r\n}"));
    }

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

      $this->assertEquals(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_DOUBLE,'range'=> ['minimal'=> 0,'maximal'=> null]]], parse('double<0>'));
      $this->assertEquals(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_DOUBLE,'range'=> ['minimal'=> -1.1,'maximal'=> 4.1]]], parse('double<-1.1,4.1>'));
      $this->assertEquals(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_DOUBLE,'range'=> ['minimal'=> 1,'maximal'=> 9]]], parse('double<1,9>'));
      $this->assertEquals(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_DOUBLE,'range'=> ['minimal'=> 1.2,'maximal'=> 3.2]]], parse('double<1.2,3.2>'));
      $this->assertEquals(['definition'=> PM1_RANGE,'body'=> ['keyword'=> PM1_DOUBLE,'range'=> ['minimal'=> null,'maximal'=> 9]]], parse('double<,9>'));

      $this->assertEquals(['definition'=> PM1_REGULAR_EXPRESSION,'body'=> ['pattern'=> '^s: \\\d+','flag'=> ['global'=> false,'case_insensitive'=> true,'multi'=> false]]], parse('/^s: \\\d+/i'));

      $this->assertEquals(['definition'=> PM1_OBJECT,'body'=> []], parse('{}'));
      $this->assertEquals(['definition'=> PM1_ENUMERATION, 'body'=> []], parse('()'));
      $this->assertEquals(['definition'=> PM1_ARRAY,'body'=> null], parse('[]'));

      $this->assertEquals(['definition'=> PM1_OBJECT,'body'=> [['name'=> 'id','is_optional'=> false,'value'=> PM1_INT],['name'=> 'account_balance','is_optional'=> true,'value'=> PM1_DOUBLE]]], parse('{ id : int ,account_balance?: double,}'));
      $this->assertEquals(['definition'=> PM1_ENUMERATION,'body'=> [1,0]], parse('(one_piece=1,0)'));
      $this->assertEquals(['definition'=> PM1_ARRAY,'body'=> ['definition'=> PM1_OBJECT,'body'=> [['name'=> 'id','is_optional'=> false,'value'=> PM1_INT]]]], parse('[{id:int}]'));
      $this->assertEquals(['definition'=> PM1_ARRAY,'body'=> PM1_INT], parse('[int]'));
    }
  }
}