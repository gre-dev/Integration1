<?php

class SessionException extends Exception
{
    // just a wrapper

    function __construct(String $message) {
        parent::__construct($message);

    }
}
?>
