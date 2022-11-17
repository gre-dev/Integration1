<?php

class SessionException extends Exception
{

    

/**
 *  ERR_* error constants used for printing the right exception message.
 **/

    // don't use enums for php < 8.1 compatibility

    const ERR_EMAIL_NOT_FOUND = 1;
    const ERR_PASS_NOT_FOUND = 2;
    const ERR_USER_TOKEN_NOT_FOUND = 15;

/**
 * @param int $code error code to set.
**/

    function __construct(int $code) {
        parent::__construct();
        
        $this->code = $code;
        $this->message = $this->getRealMessage();
    }

/**
 * updates the message based on $this->code.
 *
 * @return string a human readable exception message.
 **/
    
    private function getRealMessage() {

        $error_str = "";
        $error_str .= "Error {$this->code}: ";
        
        $error_table = array (
            self::ERR_EMAIL_NOT_FOUND  =>  "email doesn't exist in the current session",
            self::ERR_PASS_NOT_FOUND  =>  "password doesn't exist in the current session",
            self::ERR_USER_TOKEN_NOT_FOUND  =>  "login token doesn't exist in the current session"
        );
        
        $error_str .= $error_table[$this->code] ;
        return $error_str;
    }

}
?>
