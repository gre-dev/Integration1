<?php

class DBException extends Exception
{
    // just a wrapper
    private $pdoexception;

    // cons

    function __construct(String $message,PDOException $exception = null) {
        parent::__construct($message);
        $this->pdoexception = $exception;
    }

    public function get_pdo_exception() {

        return $this->pdoexception;
    }
}
?>
