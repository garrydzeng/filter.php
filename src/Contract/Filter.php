<?php
namespace GarryDzeng\Filter\Contract {
  interface Filter {
    public function filter($pathname, $data);
  }
}