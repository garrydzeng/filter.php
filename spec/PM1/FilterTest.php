<?php
namespace PM1 {

  class FilterTest extends \PHPUnit\Framework\TestCase {

    public function testFilter() {

      $struct = [
        PM1_OBJECT,
        [
          [
            'name'=> 'id',
            'is_optional'=> false,
            'value'=> [
              PM1_RANGE,
              [
                'upper_bound'=> NULL,
                'lower_bound'=> 1,
                'keyword'=> 1,
              ]
            ]
          ],
          [
            'name'=> 'openid',
            'is_optional'=> false,
            'value'=> [
              PM1_REGULAR_EXPRESSION,
              [
                'pattern'=> '^[\\da-f]{32}$',
                'flag'=> [
                  'global'=> false,
                  'case_insensitive'=> true,
                  'multi'=> true,
                ]
              ]
            ]
          ],
          [
            'name'=> 'sn',
            'is_optional'=> false,
            'value'=> 1,
          ],
          [
            'name'=> 'describe_as',
            'is_optional'=> true,
            'value'=> [
              PM1_ARRAY,
              PM1_STRING
            ]
          ],
          [
            'name'=> 'plan',
            'is_optional'=> false,
            'value'=> [
              PM1_OBJECT,
              [
                [
                  'name'=> 'space',
                  'is_optional'=> false,
                  'value'=> [
                    PM1_RANGE,
                    [
                      'upper_bound' => NULL,
                      'lower_bound' => 1,
                      'keyword' => 1,
                    ]
                  ]
                ],
                [
                  'name'=> 'private_repos',
                  'is_optional'=> false,
                  'value'=> [
                    PM1_RANGE,
                    [
                      'upper_bound'=> NULL,
                      'lower_bound'=> 0,
                      'keyword'=> 1,
                    ]
                  ]
                ],
                [
                  'name'=> 'name',
                  'is_optional'=> false,
                  'value'=> 4,
                ]
              ]
            ]
          ],
          [
            'name'=> 'type',
            'is_optional'=> false,
            'value'=> [
              PM1_ENUMERATION,
              [
                0 => 1,
                1 => 2,
                2 => 3,
                3 => 4,
                4 => 5,
                5 => 6,
              ]
            ]
          ]
        ]
      ];

      filter($struct, [
        'id'=> 1,
        'openid'=> '8945506e3e6347a0937c53833bca2cdf',
        'sn'=> 1,
        'type'=> 1,
        'plan'=> [
          'space'=> -1,
          'private_repos'=> 20,
          'name'=> 'Small'
        ]
      ]);
    }
  }
}