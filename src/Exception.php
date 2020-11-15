<?php
namespace GarryDzeng\Filter {

  use Throwable;

  class Exception extends \Exception {

    public $declaration;
    public $depth;

    public function __construct($message, $declaration, array $depth, Throwable $previous = null) {

      $this->declaration = $declaration;
      $this->depth = $depth;

      parent::__construct(
        $message,
        0x00,
        $previous
      );
    }
  }
}