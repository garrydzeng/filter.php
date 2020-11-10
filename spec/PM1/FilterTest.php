<?php
namespace GarryDzeng\PM1 {

  class FilterTest extends \PHPUnit\Framework\TestCase {

    public function testFilter() {

      $struct = array (
        'definition' => 6,
        'body' =>
          array (
            0 =>
              array (
                'name' => 'id',
                'is_optional' => false,
                'value' =>
                  array (
                    'definition' => 9,
                    'body' =>
                      array (
                        'keyword' => 1,
                        'bound' =>
                          array (
                            'minimal' => 1,
                            'maximal' => NULL,
                          ),
                      ),
                  ),
              ),
            1 =>
              array (
                'name' => 'openid',
                'is_optional' => false,
                'value' =>
                  array (
                    'definition' => 10,
                    'body' =>
                      array (
                        'pattern' => '^[\\da-f]{32}$',
                        'flag' =>
                          array (
                            'global' => true,
                            'case_insensitive' => true,
                            'multi' => true,
                          ),
                      ),
                  ),
              ),
            2 =>
              array (
                'name' => 'sn',
                'is_optional' => false,
                'value' => 1,
              ),
            3 =>
              array (
                'name' => 'describe_as',
                'is_optional' => true,
                'value' =>
                  array (
                    'definition' => 8,
                    'body' => 4,
                  ),
              ),
            4 =>
              array (
                'name' => 'plan',
                'is_optional' => false,
                'value' =>
                  array (
                    'definition' => 6,
                    'body' =>
                      array (
                        0 =>
                          array (
                            'name' => 'space',
                            'is_optional' => false,
                            'value' =>
                              array (
                                'definition' => 9,
                                'body' =>
                                  array (
                                    'keyword' => 1,
                                    'bound' =>
                                      array (
                                        'minimal' => 1,
                                        'maximal' => NULL,
                                      ),
                                  ),
                              ),
                          ),
                        1 =>
                          array (
                            'name' => 'private_repos',
                            'is_optional' => false,
                            'value' =>
                              array (
                                'definition' => 9,
                                'body' =>
                                  array (
                                    'keyword' => 1,
                                    'bound' =>
                                      array (
                                        'minimal' => 0,
                                        'maximal' => NULL,
                                      ),
                                  ),
                              ),
                          ),
                        2 =>
                          array (
                            'name' => 'name',
                            'is_optional' => false,
                            'value' => 4,
                          ),
                      ),
                  ),
              ),
            5 =>
              array (
                'name' => 'type',
                'is_optional' => false,
                'value' =>
                  array (
                    'definition' => 7,
                    'body' =>
                      array (
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        3 => 4,
                        4 => 5,
                        5 => 6,
                      ),
                  ),
              ),
          ),
      );

      filter($struct, [
        'id'=> 1,
        'openid'=> '8945506e3e6347a0937c53833bca2cdf',
        'sn'=> 1,
        'type'=> 1,
        'plan'=> [
          'space'=> -1, // Invalid
          'private_repos'=> 20,
          'name'=> 'Small'
        ]
      ]);
    }
  }
}