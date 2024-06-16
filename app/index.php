<?php

use App\Lib\Patterns\HtmlPatterns as Patterns;
use App\Lib\Validators\HtmlValidator;

require_once "lib/validators/HtmlValidator.php";
require_once "lib/patterns/HtmlPatterns.php";

$validator = new HtmlValidator(
    Patterns::TAG_A_PATTERN,
    Patterns::TAG_I_PATTERN,
    Patterns::TAG_CODE_PATTERN,
    Patterns::TAG_STRIKE_PATTERN,
    Patterns::TAG_STRONG_PATTERN,
);

// тест
var_dump($validator->validate('<strong>Unclosed strong tag</strong>')); // true
var_dump($validator->validate('<a href="http://example.com" title="Example">Link</a>')); // true
var_dump($validator->validate('<strong>Strong <i>Italic Text</strong></i>')); // false
var_dump($validator->validate('<strong>Unclosed strong tag')); // false