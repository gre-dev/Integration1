<?php

class DBException extends Exception
{
    // don't use enums for php < 8.1 compatibility

    const DB_ERR_CONN_WRONG_DATA = 1;
    const DB_ERR_SELECT = 2;
    const DB_ERR_INSERT = 3;
    const DB_ERR_UPDATE = 4 ;
    const DB_ERR_DELETE = 5;
    const DB_ERR_CUSTOM = 6; 
        
    private int $error_code;
    private $pdoexception;

    private $connection_wrong_data;
    private $select_data;
    private $insert_data;
    private $update_data;
    private $delete_data;
    
    public function __construct(int $code, PDOException $exception = null) {
        parent::__construct();

        $this->error_code = $code;
        $this->pdoexception = $exception;
        $this->message = $this->updateMessage();
    }

    public function set_connection_wrong_data($data) {
        $this->connection_wrong_data = $data;
        $this->message = $this->updateMessage();
    }
    
    public function set_select_data($data) {
        $this->select_data = $data;
        $this->message = $this->updateMessage();
    }
    
    public function set_insert_data($data) {
        $this->insert_data = $data;
        $this->message = $this->updateMessage();
    }

    public function set_update_data($data) {
        $this->update_data = $data;
        $this->message = $this->updateMessage();
    }
    
    public function set_delete_data($data) {
        $this->delete_data = $data;
        $this->message = $this->updateMessage();
    }

    private function get_pdo_exception() {
        return $this->pdoexception;
    }

    private function updateMessage() {

        $error_str = "";
        $error_str .= "Error {$this->error_code}: ";

        $error_table = array (

            self::DB_ERR_CONN_WRONG_DATA => "Error while connecting to db. connection data : {$this->connection_wrong_data}",
            self::DB_ERR_INSERT         => "Error while insert :{$this->insert_data}",
            self::DB_ERR_UPDATE         => "Error while update : {$this->update_data}",
            self::DB_ERR_DELETE         => "Error while delete : {$this->delete_data}",
            self::DB_ERR_SELECT         => "Error while select :{$this->select_data}",
            self::DB_ERR_CUSTOM        => "{$this->getmessage()}",
        );

        $error_str .= $error_table[$this->error_code] ;

        if (isset($this->pdoexception) && $this->pdoexception instanceof PDOException) {
            $error_str .= " \n" . $this->pdoexception;
        }
        
        return $error_str;
    }
}
?>
