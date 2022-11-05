<?php

class DBException extends Exception
{
    // don't use enums for php < 8.1 compatibility

    

    /**
     * @var int DB_ERR_CONN_WRONG_DATA const in exception message
     * @var int DB_ERR_SELECT const in exception message
     * @var int DB_ERR_INSERT const in exception message
     * @var int DB_ERR_UPDATE const in exception message
     * @var int DB_ERR_CUSTOM const in exception message
     **/

    const DB_ERR_CONN_WRONG_DATA = 1;
    const DB_ERR_SELECT = 2;
    const DB_ERR_INSERT = 3;
    const DB_ERR_UPDATE = 4 ;
    const DB_ERR_DELETE = 5;
    const DB_ERR_CUSTOM = 6; 

    /**
     * @var int $error_code used to print exception message.
     * @var int $pdoexception internal PDOException
     **/

    private $pdoexception;

     /**
     * @var int $connection_wrong_data extra details data variable to pass to exception.
     * @var int $select_data extra details data variable to pass to exception.
     * @var int $insert_data extra details data variable to pass to exception.
     * @var int $update_data extra details data variable to pass to exception.
     * @var int $delete_data extra details data variable to pass to exception.
     **/

    private $connection_wrong_data;
    private $select_data;
    private $insert_data;
    private $update_data;
    private $delete_data;

     /**
     * @param $code error code to set, it can be one of the ERR_* consts in this class.
     * @param PDOException $pdoexcecption internal pdoexception to set, optional.
     **/
    
    public function __construct(int $code, PDOException $exception = null) {
        parent::__construct();

        $this->code = $code;
        $this->pdoexception = $exception;
        $this->message = $this->updateMessage();
    }


    /**
     * sets extra data for the exception, it will be displayed is it.
     *
     *@param string $data extra data to set for exception message
     **/

    public function set_connection_wrong_data($data) {
        $this->connection_wrong_data = $data;
        $this->message = $this->updateMessage();
    }

    /**
     * sets extra data for the exception, it will be displayed is it.
     *
     *@param string $data extra data to set for exception message
     **/
    
    public function set_select_data($data) {
        $this->select_data = $data;
        $this->message = $this->updateMessage();
    }
    
    /**
     * sets extra data for the exception, it will be displayed is it.
     *
     *@param string $data extra data to set for exception message
     **/
   
    public function set_insert_data($data) {
        $this->insert_data = $data;
        $this->message = $this->updateMessage();
    }

    /**
     * sets extra data for the exception, it will be displayed is it.
     *
     *@param string $data extra data to set for exception message
     **/

    public function set_update_data($data) {
        $this->update_data = $data;
        $this->message = $this->updateMessage();
    }
    
    /**
     * sets extra data for the exception, it will be displayed is it.
     *
     *@param string $data extra data to set for exception message
     **/
    
    public function set_delete_data($data) {
        $this->delete_data = $data;
        $this->message = $this->updateMessage();
    }
    
    /**
     * gets the internal exception.
     *
     * @returns PDOException internal pdoexception
     **/

    private function get_pdo_exception() {
        return $this->pdoexception;
    }

    /**
     * updates the exception message, based on $error_code and extra data variables.
     *
     * @returns string the exception new message
     **/

    private function updateMessage() {

        $error_str = "";
        $error_str .= "Error {$this->code}: ";

        $error_table = array (

            self::DB_ERR_CONN_WRONG_DATA => "Error while connecting to db. connection data : {$this->connection_wrong_data}",
            self::DB_ERR_INSERT         => "Error while insert :{$this->insert_data}",
            self::DB_ERR_UPDATE         => "Error while update : {$this->update_data}",
            self::DB_ERR_DELETE         => "Error while delete : {$this->delete_data}",
            self::DB_ERR_SELECT         => "Error while select :{$this->select_data}",
            self::DB_ERR_CUSTOM        => "{$this->getmessage()}",
        );

        $error_str .= $error_table[$this->code] ;

        if (isset($this->pdoexception) && $this->pdoexception instanceof PDOException) {
            $error_str .= " \n" . $this->pdoexception;
        }
        
        return $error_str;
    }
}
?>
