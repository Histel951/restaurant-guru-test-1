<?php

namespace App\Lib\Validators;

interface Validator
{
    /**
     * @param string $html
     * @return bool
     */
    public function validate(string $html): bool;
}