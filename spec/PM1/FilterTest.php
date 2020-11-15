<?php
namespace GarryDzeng\PM1 {

  class FilterTest extends \PHPUnit\Framework\TestCase {

    public function testFilter() {

      $struct = array (
        'success' => true,
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
                            'global' => false,
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
                'value' =>
                  array (
                    'definition' => 1,
                    'body' => NULL,
                  ),
              ),
            3 =>
              array (
                'name' => 'describe_as',
                'is_optional' => true,
                'value' =>
                  array (
                    'definition' => 8,
                    'body' =>
                      array (
                        'success' => true,
                        'definition' => 4,
                        'body' => NULL,
                      ),
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
                            'value' =>
                              array (
                                'definition' => 4,
                                'body' => NULL,
                              ),
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

      [
        'definition'=> $definition,
        'body'=> $body,
      ] = $struct;



      filter(
        [
          'definition'=> $definition,
          'body'=> $body,
        ],
        [
          'id'=> 1,
          'openid'=> '8945506e3e6347a0937c53833bca2cdf',
          'sn'=> 1,
          'type'=> 998,
          'plan'=> [
            'space'=> 2, // Invalid
            'private_repos'=> 20,
            'name'=> 'Small'
          ]
        ]
      );
    }
  }
}