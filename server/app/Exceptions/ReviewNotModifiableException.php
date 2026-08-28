<?php

namespace App\Exceptions;

use Exception;

class ReviewNotModifiableException extends Exception
{
    /**
     * Create a new exception instance for attempts to modify non-pending reviews.
     *
     * @param string $message
     * @return void
     */
    public function __construct(string $message = 'Cannot modify a moderated review')
    {
        parent::__construct($message);
    }
}
