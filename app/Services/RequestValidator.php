<?php

namespace App\Services;

use InvalidRequestException;

class RequestValidator
{
    public function validate($symbol, $depth)
    {
        if ($symbol === null || $depth <= 0 || $depth > 5000) {
            throw new InvalidRequestException("Invalid request parameters.");
        }
    }
}
