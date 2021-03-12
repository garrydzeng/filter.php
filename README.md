# A Filter & Generator Library

We create a data format (called "PM1") that used to describe data structure.

## Example

see `example/index.php` for more information (include `index.pm1`)

## Specification Guide

see `doc/PM1.md` for more information

## Why?

Many existing library use instance's method & some data structure to declare validation rule:

```
use Cake\Validation\Validator;

$validator = new Validator();
$validator
    ->requirePresence('email')
    ->add('email', 'validFormat', [
        'rule' => 'email',
        'message' => 'E-mail must be valid'
    ])
    ->requirePresence('name')
    ->notEmptyString('name', 'We need your name.')
    ->requirePresence('comment')
    ->notEmptyString('comment', 'You need to give a comment.');

$errors = $validator->validate($_POST);
if (!empty($errors)) {
    // display errors.
}
```

or 

``` 
$validAccount = v::arr()                                                        // We're gonna assert an array...
                 ->key('first', $n = v::string()->notEmpty()->length(3, 32))    // With a string key "first" from 3 to 32 chars.
                 ->key('last',  $n)                                             // Reusing the same rule for "last" key
                 ->key('day', v::notEmpty())                                    // Must have a key "date" not empty
                 ->key('month', v::notEmpty())                                  // Must have a key "month" not empty
                 ->key('year', v::notEmpty())                                   // Must have a key "year" not empty
                 ->call(function ($acc) {                                       // Calls this function on the passed array  (will be $_POST)
                    return sprintf(                                             // Formats a string...
                        '%04d-%02d-%02d',                                       // To this date format, padding the numbers with zeroes
                        $acc['year'],
                        $acc['month'], 
                        $acc['day']
                    );
                 }, v::date('Y-m-d')->minimumAge($minimumAge))                  // Then get the fomatted string and validate date and minimum age.
                 ->setName('the New Account');                                  // Naming this rule!
```

Or ...

```
<?php

use Cake\Validation\Validator;
use Selective\Validation\Converter\SymfonyValidationConverter;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\Regex\ValidationRegex;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

// Note: This is just an example. Don't use the $request object within the domain layer.
$formData = (array)$request->getParsedBody();

// Create a symfony validator instance
$validator = Validation::createValidator();

// Add rules
$constraint = new Assert\Collection(
    [
        'first_name' => new Assert\NotBlank(['message' => 'Input required']),
        'last_name' => new Assert\NotBlank(['message' => 'Input required']),
        'mobile' => new Assert\Optional(
            [
                new Assert\Regex(
                    [
                        'pattern' => ValidationRegex::PHONE_NUMBER,
                        'message' => 'Invalid',
                    ]
                ),
            ]
        ),
        'comment' => new Assert\Optional(
            [
                new Assert\Length(['max' => 255, 'maxMessage' => 'Too long']),
            ]
        ),
        'email' => new Assert\Optional(
            [
                new Assert\Email(
                    [
                        'message' => 'Invalid',
                    ]
                ),
            ]
        )
    ]
);
```

They have two problems ...

- Developer has to organize rule by some mechanism (modular for reuse)
- It brings a lot of function call overhead.

