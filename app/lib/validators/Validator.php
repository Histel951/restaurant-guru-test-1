<?php

namespace App\Lib\Validators;

interface Validator
{
    /**
     * @param string $requestData
     * @return bool
     */
    public function validate(string $requestData): bool;
}