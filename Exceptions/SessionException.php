<?php

class SessionException extends Exception
{

    // don't use enums for php < 8.1 compatibility

    const ERR_DATA_NOT_FOUND = 1;
    
    private int $error_code;

    function __construct(int $code) {
        parent::__construct();

        $this->error_code = $code;
        $this->message = $this->getRealMessage();
    }

    
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
