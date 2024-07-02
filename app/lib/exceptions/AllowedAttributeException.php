<?php

declare(strict_types=1);

namespace App\Lib\Exceptions;

use Exception;
use Throwable;

class AllowedAttributeException extends Exception
{
    public function __construct(
        string $message = "Ошибка при проверке на допустимые атрибут для HTML тега.",
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}