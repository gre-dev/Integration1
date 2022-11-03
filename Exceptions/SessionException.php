<?php

class SessionException extends Exception
{

    

/**
 * @var ERR_DATA_NOT_FOUND an error constant used for printing exception message.
 * @var int $error_code error code, used for printing exception message
 **/

    // don't use enums for php < 8.1 compatibility

    const ERR_DATA_NOT_FOUND = 1;
    
    private int $error_code;

/**
 * @param int $code error code to set.
**/

    function __construct(int $code) {
        parent::__construct();
        
        $this->error_code = $code;
        $this->message = $this->getRealMessage();
    }

/**
 * updates the message based on $error_code.
 *
 * @return string a human readable exception message.
 **/
    
    private function getRealMessage() {

        $error_str = "";
        $error_str .= "Error {$this->error_code}: ";
        
        $error_table = array (
            self::ERR_DATA_NOT_FOUND  =>  "login data (password or email) does't exist in current session",
        );
        
        $error_str .= $error_table[$this->error_code] ;
        return $error_str;
    }

}
?>
