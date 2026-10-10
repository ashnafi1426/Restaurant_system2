<?php

namespace App\Exceptions;

use Exception;

class DuplicateVoteException extends Exception
{
    /**
     * Create a new exception instance for duplicate vote attempts.
     *
     * @param string $message
     * @return void
     */
    public function __construct(string $message = 'You have already voted on this review')
    {
        parent::__construct($message);
    }
}

