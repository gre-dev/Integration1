<?php

class EmptyBodyRequestException extends Exception
{
    public function __construct() {
        parent::__construct();
        
        $this->code = 18;
        $this->message = 'you send an api request with a missing body';
    }
}

?>
