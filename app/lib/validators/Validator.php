<?php

namespace App\Lib\Validators;

interface Validator
{
    /**
     * @param mixed $data
     * @return bool
     */
    public function validate($data): bool;
}