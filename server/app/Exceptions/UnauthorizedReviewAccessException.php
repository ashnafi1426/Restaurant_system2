<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedReviewAccessException extends Exception
{
    /**
     * Create a new exception instance for unauthorized review access attempts.
     *
     * @param string $message
     * @return void
     */
    public function __construct(string $message = 'Unauthorized access')
    {
        parent::__construct($message);
    }
}
