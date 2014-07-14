<?php namespace Andreyco\Instagram\Exception;

use Exception;

class CurlException extends Exception {

    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        $message = sprintf('Error: _makeCall() - cURL error:  %s', $message);

        parent::__construct($message);
    }

}
