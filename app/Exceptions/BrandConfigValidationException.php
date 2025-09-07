<?php

namespace App\Exceptions;

use Exception;

class BrandConfigValidationException extends Exception
{
    public function __construct(string $message = "Brand configuration validation failed")
    {
        parent::__construct($message);
    }
}