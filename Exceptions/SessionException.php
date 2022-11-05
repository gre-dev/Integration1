<?php

class SessionException extends Exception
{

    

/**
 * @var ERR_DATA_NOT_FOUND an error constant used for printing exception message.
 **/

    // don't use enums for php < 8.1 compatibility

    const ERR_DATA_NOT_FOUND = 1;

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
            self::ERR_DATA_NOT_FOUND  =>  "login data (password or email) does't exist in current session",
        );
        
        $error_str .= $error_table[$this->code] ;
        return $error_str;
    }

}
?>
