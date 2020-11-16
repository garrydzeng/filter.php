<?php
namespace GarryDzeng\Filter {

  use InvalidArgumentException;

  use function GarryDzeng\PM1\filter;
  use function GarryDzeng\PM1\generate;
  use function GarryDzeng\PM1\parse;

  class Filter implements Contract\Filter {

    private $cache;

    public function __construct($cache = null) {
      $this->cache = ($cache == '') ? sys_get_temp_dir().DIRECTORY_SEPARATOR.'pm1' : $cache;
    }

    private function resolve($pathname) {

      $pathname = realpath($pathname);

      if (!$pathname) {
        throw new InvalidArgumentException(
          'Unreachable pathname. 
           its not valid absolute pathname or file does not exists, 
           please check.'
        );
      }

      // Path to PHP script
      $executable = $this->cache.DIRECTORY_SEPARATOR.md5($pathname);

      // Check if it doesn't expired
      if (file_exists($executable) && filemtime($executable) >= filemtime($pathname)) {
        return
          require($executable)
        ;
      }

      $struct = parse(file_get_contents($pathname));

      [
        'success'=> $success,
        'error'=> $error,
        'definition'=> $definition,
        'body'=> $body,
      ] = $struct;

      /*
       * Syntax error can avoid by manual in advance,
       * report by assertion
       * is enough
       */
      assert($success, "Incorrect or empty Notation found: ". ($error ?? '∅'));

      $struct = [
        'definition'=> $definition,
        'body'=> $body,
      ];

      // ensure directory
      mkdir(pathinfo($executable, PATHINFO_DIRNAME), 0777, true);

      // refresh file contents if outdated
      file_put_contents(
        $executable,
        sprintf("<?php\nreturn %s;", var_export(
          $struct,
          true
        ))
      );

      return $struct;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function filter($pathname, $data) {

      [
        'success'=> $success,
        'error'=> $error,
        'declaration'=> $declaration,
        'depth'=> $depth
      ] = filter(
        $this->resolve($pathname),
        $data
      );

      // throws exception if data doesn't fulfill the declaration.
      // with human friendly message
      if (!$success) {
        throw new Exception(
          $error,
          $declaration,
          $depth
        );
      }
    }
  }
}