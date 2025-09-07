<?php

namespace App\Exceptions;

use Exception;

class BrandConfigDeletionException extends Exception
{
    public function __construct(string $message = "Brand configuration deletion blocked")
    {
        parent::__construct($message);
    }
}