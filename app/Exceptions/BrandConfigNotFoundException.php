<?php

namespace App\Exceptions;

use Exception;

class BrandConfigNotFoundException extends Exception
{
    public function __construct(string $message = "Brand configuration not found")
    {
        parent::__construct($message);
    }
}