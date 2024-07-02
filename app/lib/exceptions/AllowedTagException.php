<?php

declare(strict_types=1);

namespace App\Lib\Exceptions;

use Exception;
use Throwable;

class AllowedTagException extends Exception
{
    public function __construct(string $message = "Ошибка при проверке на допустимые HTML теги.", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}