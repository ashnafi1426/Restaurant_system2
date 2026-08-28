<?php

namespace App\Exceptions;

use Exception;

class PurchaseNotVerifiedException extends Exception
{
    /**
     * Create a new exception instance for unverified purchase.
     *
     * @param string $message
     * @return void
     */
    public function __construct(string $message = 'You must order this item before reviewing it')
    {
        parent::__construct($message);
    }
}
