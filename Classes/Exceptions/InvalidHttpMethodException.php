<?php

class InvalidHttpMethodException extends Exception
{
    public function __construct() {
        parent::__construct();
        
        $this->code = 16;
        $this->message = 'Invalid http method for this route';
    }
}
?>
