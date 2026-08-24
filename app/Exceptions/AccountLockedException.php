<?php

namespace App\Exceptions;

class AccountLockedException extends \RuntimeException
{
    public function __construct(string $message = 'Your account has been temporarily locked. Please try again later.')
    {
        parent::__construct($message);
    }
}
