<?php

namespace App\GeojsonConverter\Exception;

class InvalidXmlException extends \Exception {
    public function __construct($message = "Invalid XML format.", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
