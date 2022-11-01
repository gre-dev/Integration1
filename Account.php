<?php

require_once 'API.php';
require_once './Exceptions/DBException.php';
require_once './Exceptions/SessionException.php';
require_once './Traits/InputHandleTrait.php';

require_once 'vendor/autoload.php';

class Account {

    use InputHandleTrait;

    private $dbhost;
    private $dbname;
    private $dbuser;
    private $dbpass='password';

    private $api_keys_table;
    private $accounts_table;

    private $PLAN_FREE_ID;
    
    private $db;

    public function __construct() {

        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);
        $dotenv->required(['DB_ACCOUNTS_TABLE', 'DB_API_KEYS_TABLE']);

        $this->dbhost = $_ENV['DB_HOST'];
        $this->dbname = $_ENV['DB_NAME'];
        $this->dbuser = $_ENV['DB_USER'];
        $this->dbpass = $_ENV['DB_PASSWORD'];
        $this->api_keys_table = $_ENV['DB_API_KEYS_TABLE'];
        $this->accounts_table = $_ENV['DB_ACCOUNTS_TABLE'];

        $dotenv->ifPresent('PLAN_FREE_ID')->isInteger();
        $this->PLAN_FREE_ID = $_ENV['PLAN_FREE_ID'] ?? 1;

    }
        
    private function db_connect() {
        try {            
            $this->db = new PDO("mysql:host={$this->dbhost};dbname={$this->dbname}", $this->dbuser, $this->dbpass);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $this->db;

        } catch (PDOException $e) {
            
            $exception = new DBException(DBException::DB_ERR_CONN_WRONG_DATA,$e);
            $exception->set_connection_wrong_data("Error while connecting to db, please check if server login credentials is correct: Username {$this->dbuser}, Password {$this->dbpass}");
            throw $exception;
        }
    }

    private function db_close_connection() {
        $this->db = null;
    }

    public function check_session() { //use session_start() outside this function

        if (isset($_SESSION['login_email']) && isset($_SESSION['login_password'])) {
            
            $email = $_SESSION['login_email'];
            $password = $_SESSION['login_password'];

            $email = $this->revert_html_filter($email);
            $password = $this->revert_html_filter($password);

            if (empty($email) || empty($password)) {
                throw new SessionException(SessionException::ERR_DATA_NOT_FOUND);
            }
            
            try {
                $db = $this->db_connect();

                // note that login_password in session is a hash , notice
                $stmt = $db->prepare("SELECT COUNT(id) FROM {$this->accounts_table} WHERE email = :email AND password = :pass LIMIT 1");

                $stmt->bindValue(':email',$email);
                $stmt->bindValue(':pass',$password);

                $stmt->execute();
                $rows = $stmt->fetchColumn();
                
                if ($rows === 1) {
                    $this->db_close_connection();
                    return true;
                }
            }
            catch (PDOException $e) {
                $exception = new DBException(DBException::DB_ERR_SELECT,$e);
                $exception->set_select_data( "Error while checking user session data in db");
                throw  $exception;
            }
        }
        $this->db_close_connection();
        return false;
        
    } 
            

    public function update_session_data($email, $pass, $is_hashed = true) //use session_start() outside this function
    {

        
        // maybe checking account password is strong, notice
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email passed doesn't look like an email string");
        }

        session_start();
        $_SESSION['login_email'] = $this->filter_html_input($email);
        $_SESSION['login_password'] = $this->filter_html_input($pass);
    }
    
    public function validate_login_credentials($email,$pass)
    {
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email passed doesn't look like an email string");
        }
        
        if (empty($email) || empty($pass)) {
            throw new InvalidArgumentException("Some or all args are empty strings");
        }

        try {
  
            $db = $this->db_connect();

            $stmt = $db->prepare("SELECT * FROM {$this->accounts_table} WHERE email = :email AND password = :pass LIMIT 1");
                
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':pass' , $pass);
                
            $success = $stmt->execute();

            if (!$success) {
                $exception = new DBException(DBException::DB_ERR_SELECT);
                $exception->set_select_data("Error while performing login credentials checking queries: Email $email, Password $pass");
                throw $exception;
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if ($user) {
                $this->db_close_connection();
                return $user ;
            }
            
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_SELECT);
            $exception->set_select_data("while performing login credentials checking queries: Email $email, Password $pass");
            throw $exception;
        }

        // for properly close if anything behave unexpectably
        $this->db_close_connection();
        return []; 
    }


    public function create_new_account($email,$password,$username) { // check if them are required to function or just username
        
        try {
            if (empty($password) || empty($username)) {
                throw new InvalidArgumentException("Some args are empty strings");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Email arg doesn't look like an email string");
            }
            
            $db = $this->db_connect();

            $stmt = $db->prepare("INSERT INTO {$this->accounts_table} (username,email, password, time) VALUES (:username, :email, :pass, :time)");

            if ($is_hashed === false) {
                $hashoptions = [
                    'cost' => 12,
                ];
                
                $password =    password_hash($password, PASSWORD_BCRYPT, $hashoptions);
            }
            
            $data = [
                'username' => $username,
                'email'    => $email,
                'pass'     => $password,
                'time'    => time()
                
            ];
            
            $result = $stmt->execute($data);

            if ($result === false || $stmt->rowCount() !== 1) {
                $exception = new DBException(DBException::DB_ERR_INSERT);
                $exception->set_insert_data("Error while adding a new account");
                throw $exception;
            }
                
            $accountId = $db->lastInsertId();
            
            $api = new API();
            $keyid = $api->create_new_key($accountId, $this->PLAN_FREE_ID); // maybe change it after we decides about plan tables

            $this->db_close_connection();
            return $accountId;
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_INSERT);
            $exception->set_insert_data("Error while adding a new account to db");
            throw $exception;
        }
        $this->db_close_connection();
        $this->update_session_data($email, $pass); 
    }
    
    public function is_email_avaliable($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email passed $email doesn't looks like sanitized email");
        }
        
        try {
            $db = $this->db_connect();
            
            $stmt = $db->prepare("SELECT COUNT(id) FROM {$this->accounts_table} WHERE email = :email");
            $result = $stmt->execute(array($email));
            
            if (!$result || $passStmt->rowCount() !== 1)
            {
                $exception = new DBException(DBException::DB_ERR_SELECT);
                $exception->set_select_data("Error while checking if email already exists");
                throw $exception;
            }

            $rows = $stmt->fetchColumn();
            $this->db_close_connection();
            return ($rows === 0);
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_SELECT,$e);
            $exception->set_select_data("Error while checking if email already exists in db");
            throw $exception;
        }
        
        $this->db_close_connection();
        return false;
    }

    private function base62_encode ($data) {
        /* 
         * Used to hash reset password token correctly,
         * avoiding possibles clashes in url generation
         *
         */
        $outstring = '';
        $len = strlen($data);
        for ($i = 0; $i < $len; $i += 8) {
            $chunk = substr($data, $i, 8);
            $outlen = ceil((strlen($chunk) * 8) / 6);
            $x = bin2hex($chunk);
            $number = ltrim($x, '0');
            if ($number === '') $number = '0';
            $w = gmp_strval(gmp_init($number, 16), 62);
            $pad = str_pad($w, $outlen, '0', STR_PAD_LEFT);
            $outstring .= $pad;
        }
        return $outstring;
    }

    public function generate_password_token($id) {
        // generate it and store in db

        $random = openssl_random_pseudo_bytes(12); // this will make 33-char length string with hex
        $random =  bin2hex($random);
        
        // we will use base62 encode in order to avoid URL clashes issues
        $encoded_token = $this->base62_encode($random);
        
        $encoded_token = strtolower($encoded_token); // beutify only

        try {
            
            $db = $this->db_connect();
    
            $stmt = $db->prepare("UPDATE {$this->accounts_table} SET reset_pass_token = :token where id = :accountid");

            $data = array (
                'token' => $encoded_token,
                'accountid' => $id
            );
            
            $result = $stmt->execute($data);
    
            if (!$result || $stmt->rowCount() !== 1) // affected rows
            {
                $exception = new DBException (DBException::DB_ERR_INSERT);
                $exception->set_insert_data("Error while storing password token for account $id");
                throw $exception;
            }
        }
        catch (PDOException $e) {   
            $exception = new DBException (DBException::DB_ERR_INSERT,$e);
            $exception->set_insert_data("Error while storing password token for account $id in db");
            throw $exception;
        }
        
        $this->db_close_connection();
        return $encoded_token;
    }

    public function update_password($email,$password, $is_hashed = true) {

        if (empty($password) || empty($email)) {
            throw new InvalidArgumentException ('Either email or password args passed is empty');
        }
        
        try {
            
            $db = $this->db_connect();
    
            $stmt = $db->prepare("SELECT id FROM {$this->accounts_table} WHERE email = ? LIMIT 1");
            $stmt->execute(array($email));

            $accountId = $stmt->fetchColumn();
        
            if (!$accountId)
            {
                $exception = new DBException(DBException::DB_ERR_UPDATE);
                $exception->set_update_data("Error while updating password for an account");
                throw $exception;
            }

        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_UPDATE,$e);
            $exception->set_update_data("Error while updating password for an account");
            throw $exception;
        }

        if ($is_hashed === false) {
            $hashoptions = [
                'cost' => 12,
            ];
            $passhash =   password_hash($password, PASSWORD_BCRYPT, $hashoptions);
        }
        else {
            $passhash = $password;
        }
        
        try {

            $passStmt = $db->prepare("UPDATE {$this->accounts_table} SET password = :pass where id = :accountid");


            $data = array(
                'pass' => $passhash,
                'accountid' => $accountId
            );
                  
            $result = $passStmt->execute($data);

            if (!$result)
            {
                $exception = new DBException(DBException::DB_ERR_UPDATE);
                $exception->set_update_data("password for account $accountId");
                throw $exception;
            }
            $this->update_session_data($email, $passhash);

        }        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_UPDATE, $e);
            $exception->set_update_data("Error while updating password for account $accountId in db");
            throw $exception;
        }
            
        $this->db_close_connection();
    }

    public function logout() { //session_start() outside this function
        session_start();
        unset($_SESSION['login_email']);
        unset($_SESSION['login_password']);
    }

}

?>
    
