<?php
class API {

    private $dbname = 'first';
    private $db_user = 'root';
    private $db_pass = 'password';
    
    private $api_keys_table = 'api_keys';
    private $accounts_table = 'accounts';
    private $plans_table = 'api_keys_plans';

    private function db_connect() {        
        try {
            $this->db = new PDO("mysql:host=localhost;dbname={$this->dbname}", $this->db_user, $this->db_pass);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (PDOException $e) {
            die();
        }
        return $this->db;
    }

    private function db_close_connection() {
        $this->db = null;
    }

    public function create_new_key($accountid,$planid)
    {
        // notice about plans schema, notify it        
        try {
            
            $db = $this->db_connect();

            $planStmt = $db->prepare("SELECT COUNT(id) from {$this->plans_table} where id = ?");
            $result = $planStmt->execute(array($planid));
            
            if (!$result || $planStmt->fetchColumn() === 0)
            {
                die();
            }
            
            $accountStmt = $db->prepare("SELECT COUNT(id) from {$this->accounts_table} where id = ?");
            $result = $accountStmt->execute(array($accountid));
            
            if (!$result || $accountStmt->fetchColumn() === 0)
            {
                die();
            }
          
            $stmt = $db->prepare("INSERT INTO {$this->api_keys_table} (account_id, api_key, requests, plan_id, date) VALUES (:accountid, :apikey, :requests, :planid, :date)");
            $data = [
                'accountid' => $accountid,
                'apikey' => $this->generate_apikey_string(), 
                'requests' => 0, // represent api key current requests, not the plan limit
                'planid' => $planid,
                'date' => time()
               
            ];
            $result = $stmt->execute($data);

            if (!$result) { die(); }

            $keyid = $db->lastInsertId();
            
            $this->regenerate_key($keyid);
            
            return $keyid;
        }
        catch (PDOException $e) {
            die();
        }
        
        $this->db_close_connection();        
        return NULL;
        
    }
        
    public function generate_apikey_string () {
        // until we change implementation, this is enough now
            
        return uniqid(); 
    }
        
    public function regenerate_key($keyid) { 
        $key = $this->generate_apikey_string();
        
        try {
        
            $db = $this->db_connect();

            $stmt = $db->prepare("UPDATE {$this->api_keys_table} SET api_key = :key WHERE id = :id");
            $data = array (
                'key' => $key,
                'id' => $keyid
            );
            
            $result = $stmt->execute($data);
            
            if (!$result) { die() ; }
        }
        catch (PDOException $e) {
            die();
        }

    } 

    public function change_account_plan($keyid, $targetplanid)
    {
        try {
            if (empty($keyid) || empty($targetplanid)) {
                die();
            }
            $db = $this->db_connect();

            $planStmt = $db->prepare("SELECT COUNT(id) from {$this->plans_table} where id = ?");
            $result = $planStmt->execute(array($targetplanid));
            
            if (!$result || $planStmt->fetchColumn() === 0)
            {
                die();
            }
            
            $stmt = $db->prepare("UPDATE {$this->api_keys_table} SET plan_id = :planid where id = :id");

            $data  = [
                'planid' => $targetplanid,
                'id'     => $keyid
            ];
        
            $result = $stmt->execute($data);
            
            if (!$result)
            {
                die();
            }
    
        }
        catch (PDOException $e) {
            die();
        }
    }
              
    public function get_plan_name($keyid) {
        try {
            $db = $this->db_connect();
            $keyStmt = $db->prepare("SELECT COUNT(id) from {$this->api_keys_table} where id = ?");

            $result = $keyStmt->execute(array($keyid));
            
            if (!$result || $keyStmt->fetchColumn() === 0)
            {
                die();
            }
            
            $cmpStmt = $db->prepare("SELECT {$this->plans_table}.name FROM {$this->api_keys_table}, {$this->plans_table} WHERE {$this->api_keys_table}.id = ? AND {$this->plans_table}.id = {$this->api_keys_table}.plan_id");
            
            $cmpStmt->execute(array($keyid));

            if (!$result) die();
        
            $name = $cmpStmt->fetchColumn();

            $this->db_close_connection();
            return $name;
        }
        catch (PDOException $e) {

            die();
        }
        
        $this->db_close_connection();
        return null;
    }
        
    public function get_account_plan_expirty($keyid) { 

        try {
            $db = $this->db_connect();
            
            $keyStmt = $db->prepare("SELECT date,plan_id FROM {$this->api_keys_table} WHERE id = ?");
            $keyStmt->execute(array($keyid));
            $key = $keyStmt->fetch(PDO::FETCH_OBJ);

            if (!$key)
            {
                die();
            }

            $startdate = $key->date;
            
            $planPeroidStmt = $db->prepare("SELECT period FROM {$this->plans_table} WHERE id = ?");
            $planPeroidStmt->execute(array($key->plan_id));
            $planperiod = $planPeroidStmt->fetchColumn();
            
            if (!$planperiod)
            {
                die();
            }
            
            $this->db_close_connection();
            return $planperiod + $startdate; 

        }
        catch (PDOException $e) {
            die();
        }

        $this->db_close_connection();
        return NULL;  
    }

    public function get_referrer($keyid)  {
        try {

            $db = $this->db_connect();
            
            $stmt = $db->prepare("SELECT referrer FROM {$this->api_keys_table} WHERE id = :id");

            if ($stmt->execute(array($keyid))) {
                $referrer = $stmt->fetchColumn();
                
                $this->db_close_connection();
                return $referrer;
            };
        }

        catch (PDOException $e) {
            die();
        }
        
        $this->db_close_connection();
        return null;
        
    }
    
    public function get_remaining_quotas($keyid)
    {
        try {
            $db = $this->db_connect();
            
            $keyStmt = $db->prepare("SELECT requests,plan_id FROM {$this->api_keys_table} WHERE id = ?"); 
            $keyStmt->execute(array($keyid));
            $key = $keyStmt->fetch(PDO::FETCH_OBJ);

            if (!$key)
            {
                die();
            }

            $requests = $key->requests;
            $planid = $key->plan_id;

            $planStmt = $db->prepare("SELECT max_requests FROM {$this->plans_table} WHERE id = ?");
            $planStmt->execute(array($planid));
            $planrequests = $planStmt->fetchColumn();

            
            if ($planrequests === false)
            {
                die();
            }

            $this->db_close_connection();
            return $planrequests - $requests < 0 ? 0 : $planrequests - $requests ;
        }
        
        catch (PDOException $e) {
            die();
        }
        $this->db_close_connection();
        return null;
    }
}

?>
