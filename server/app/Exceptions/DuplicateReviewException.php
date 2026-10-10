<?php

namespace App\Exceptions;

use Exception;

class DuplicateReviewException extends Exception
{
    /**
     * Create a new exception instance for duplicate review attempts.
     *
     * @param string $message
     * @return void
     */
    public function __construct(string $message = 'You have already reviewed this item for this order')
    {
        parent::__construct($message);
    }
}

