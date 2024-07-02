<?php

use App\Lib\Patterns\HtmlPatterns as Patterns;
use App\Lib\Patterns\AttributePatterns as Attribute;
use App\Lib\Validators\HtmlValidator;

require_once "lib/validators/HtmlValidator.php";
require_once "lib/patterns/HtmlPatterns.php";
require_once "lib/patterns/AttributePatterns.php";

$validator = new HtmlValidator([
    Patterns::TAG_A_PATTERN,
    Patterns::TAG_I_PATTERN,
    Patterns::TAG_CODE_PATTERN,
    Patterns::TAG_STRIKE_PATTERN,
    Patterns::TAG_STRONG_PATTERN,
], [
    Attribute::ATTRIBUTE_HREF_PATTERN,
    Attribute::ATTRIBUTE_TITLE_PATTERN,
]);

// Тесты
var_dump($validator->validate('<strong>Жирный шрифт</strong>')); // true
var_dump($validator->validate('<a href="https://google.com" title="Google">google</a>')); // true
var_dump($validator->validate('<a title="Google">google</a>')); // true
var_dump($validator->validate('<a href="https://google.com">google</a>')); // true
var_dump($validator->validate('<div>test</div><a href="https://google.com" title="Google">google</a>')); // false (недопустимый тег <div>)
var_dump($validator->validate('<a href="https://google.com" title="Google" data-test="1">google</a>')); // false (недопустимый атрибут data-test)
var_dump($validator->validate('<strong>Жирный текст <i>италик с неправильным закрытием тегов</strong></i>')); // false (неправильное закрытие тегов)
var_dump($validator->validate('<strong>Незакрытый тег')); // false (незакрытый тег)

// Тесты если передать не все возможные паттерны
$validatorATag = new HtmlValidator([
    Patterns::TAG_A_PATTERN,
], [
    Attribute::ATTRIBUTE_HREF_PATTERN
]);

var_dump($validatorATag->validate('<a href="https://google.com">google</a>')); // true
var_dump($validatorATag->validate('<a href="https://google.com" title="Google">google</a>')); // false (недопустимый аттрибут title)
var_dump($validatorATag->validate('<i><a href="https://google.com">google</a></i>')); // false (недопустимый тег <i>)