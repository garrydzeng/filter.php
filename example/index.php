<?php
require '../vendor/autoload.php';

$input = [
  'id'=> 1,
  'account_balance'=> 192.23,
  'category'=> 0,
  'created_datetime'=> '2020-11-21T06:55:13+00:00',
  'tags'=> [
    'octocat',
    'atom',
    'electron',
    'api'
  ]
];

$filter = new GarryDzeng\Filter\Filter(__DIR__);

try {

  $filter->filter(
    './index.pm1',
    $input
  );
}
catch (GarryDzeng\Filter\Exception $exception) {
  print_r($exception);
}