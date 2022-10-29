<?php

require_once 'API.php';
    
class Account {

    private $dbname='first';
    private $user='root';
    private $pass='password';

    private $api_keys_table = 'api_keys';
    private $accounts_table = 'accounts';

    private $PLAN_FREE_ID = 1;
    
    private $db;

    private function db_connect() {        
        try {
            $this->db = new PDO("mysql:host=localhost;dbname={$this->dbname}", $this->user, $this->pass);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $this->db;

        } catch (PDOException $e) {
            die();
        }
    }

    private function db_close_connection() {
        $this->db = null;
    }

    public function check_session() { //use session_start() outside this function

        if (isset($_SESSION['login_email']) && isset($_SESSION['login_password'])) {
            
            $email = $_SESSION['login_email'];
            $password = $_SESSION['login_password'];
            
            if (empty($email) || empty($password)) {
                return false;
            }
            
            try {
                $db = $this->db_connect();

                // note that login_password in session is a hash , notice
                $stmt = $db->prepare("SELECT COUNT(id) FROM {$this->accounts_table} WHERE email = :email AND password = :pass");

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
                die();
            }
        }
        $this->db_close_connection();
        return false;
        
    } 
            

    public function update_session_data($email, $pass) //use session_start() outside this function
    {
        
        // maybe checking account password is strong, notice
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die();
        }

        session_start();
        $_SESSION['login_email'] = $email;
        $_SESSION['login_password'] = $pass;
    }

    public function validate_login_credentials($email,$pass)
    {
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        if (empty($email) || empty($pass)) { return false; }

        try {
  
            $db = $this->db_connect();

            $stmt = $db->prepare("SELECT * FROM {$this->accounts_table} WHERE email = :email AND password = :pass");
                
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':pass' , $pass);
                
            $success = $stmt->execute();

            if (!$success) {
                die();
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if ($user) {
                $this->db_close_connection();
                return $user ;
            }
            
        }
        catch (PDOException $e) {
            die();
        }

        // for properly close if anything behave unexpectably
        $this->db_close_connection();
        return []; 
    }


    public function create_new_account($email,$password,$username) { // check if them are required to function or just username
        
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password) || empty($username)) {
                die();
            }
            
            
            $db = $this->db_connect();

            $stmt = $db->prepare("INSERT INTO {$this->accounts_table} (username,email, password) VALUES (:username, :email, :pass)");

            $hashoptions = [
                'cost' => 12,
            ];
            
            $data = [
                'username' => $username,
                'email'    => $email,
                'pass'     =>  password_hash($password, PASSWORD_BCRYPT, $hashoptions)
            ];
            
            $result = $stmt->execute($data);
        
            if ($result === false || $stmt->rowCount() !== 1) {
                die();
            }
                
            $accountId = $db->lastInsertId();
            
            $api = new API();
            $keyid = $api->create_new_key($accountId, $this->PLAN_FREE_ID); // maybe change it after we decides about plan tables

            $this->db_close_connection();
            return $accountId;
        }
        catch (PDOException $e) {           
            die();
        }
        $this->db_close_connection();
        $this->update_session_data($email, $pass); 
    }
    
    public function is_email_avaliable($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die();
        }
        
        try {
            $db = $this->db_connect();
            
            $stmt = $db->prepare("SELECT COUNT(id) FROM {$this->accounts_table} WHERE email = :email");
            $result = $stmt->execute(array($email));
            
            if (!$result)
            {
                die();
            }

            $rows = $stmt->fetchColumn();
            $this->db_close_connection();
            return ($rows === 0);
        }
        catch (PDOException $e) {
            die();
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
                die();
            }
        }
        catch (PDOException $e) {
            die();
        }
        
        $this->db_close_connection();
        return $encoded_token;
    }

    public function update_password($email,$password) {

        // note that you have to pass password as normal string not as hash
        
        if (empty($password) || empty($email)) {
            die();
        }
        
        try {
            
            $db = $this->db_connect();
    
            $stmt = $db->prepare("SELECT id FROM {$this->accounts_table} WHERE email = ?");

            $stmt->execute(array($email));

            $accountId = $stmt->fetchColumn();

            $hashoptions = [
                'cost' => 12,
            ];
            $passhash =  password_hash($password, PASSWORD_BCRYPT, $hashoptions);
            
            $passStmt = $db->prepare("UPDATE {$this->accounts_table} SET password = :pass where id = :accountid");

            $data = array(
                'pass' => $passhash,
                'accountid' => $accountId
            );
                  
            $result = $passStmt->execute($data);
    
            if (!$result || $passStmt->rowCount() !== 1)
            {
                die();
            }

            $this->update_session_data($email, $passhash);
        }
        catch (PDOException $e) {
            die();
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
    
