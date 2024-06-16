<?php

namespace App\Lib\Validators;

interface Validator
{
    public function validate(string $requestData): bool;
}