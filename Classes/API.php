<?php

class API {   
    /**
     * @var string $dbhost mysql db host name or ip to use when quering data.
     *
     * @var string $dbname mysql db name to get data from.
     *
     * @var string $dbuser mysql db user to use.
     *
     * @var string $dbpass mysql db password to use
     *
     **/

    private $dbhost;
    private $dbname;
    private $dbuser;
    private $dbpass;

    /**
     * @var string $api_keys_table the api keys table name.
     * @var string $accounts_table the accounts table name.
     * @var string $plans_table the plan table name.
     * @var string $api_subscriptions_table api subscriptions table name.
     **/

    private $api_keys_table;
    private $accounts_table;
    private $plans_table;
    private $requests_info_table;
    private $api_subscriptions_table;

    /**
     * @var PDO $db PDO database object, don't use it directly inside
     * class method (instead use db_connect and db_close_connection),
     * exists for saving its reference only out after a function ends
     * executions.      
     **/

    private $db;
    
    /**
     * @todo add phpdotenv exception documentation.
     **/

    public function __construct() {

        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
        $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);
        $dotenv->required(['DB_ACCOUNTS_TABLE']);
        $dotenv->required(['DB_API_KEYS_TABLE','DB_PLANS_TABLE','DB_REQUESTS_INFO_TABLE']);

        $this->dbhost = $_ENV['DB_HOST'];
        $this->dbname = $_ENV['DB_NAME'];
        $this->dbuser = $_ENV['DB_USER'];
        $this->dbpass = $_ENV['DB_PASSWORD'];
        
        $this->api_keys_table = $_ENV['DB_API_KEYS_TABLE'];
        $this->accounts_table = $_ENV['DB_ACCOUNTS_TABLE'];
        $this->plans_table = $_ENV['DB_PLANS_TABLE'];
        $this->requests_info_table = $_ENV['DB_REQUESTS_INFO_TABLE'];
        $this->api_subscriptions_table = $_ENV['DB_API_SUBSCRIPTIONS_TABLE'];
        
    }
    /**
     * establishes db connection.
     *
     * @return PDO represents the db connection object
     *
     * @throws DBException if an error encourtered while connecting to mysql db server
     */

    private function db_connect() {        
        try {
            $this->db = new PDO("mysql:host={$this->dbhost};dbname={$this->dbname}", $this->dbuser, $this->dbpass);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (PDOException $e) {

            $exception = new DBException(DBException::DB_ERR_CONN_WRONG_DATA,$e);
            $exception->set_connection_wrong_data("Error while connecting to db.");
            throw $exception;
        }
        return $this->db;
    }
    /**
     * closes db connection established by db_connect().
     *
     **/
    
    private function db_close_connection() {
        $this->db = null;
    }

    /**
     * creates a new api key with a free plan asscoiated to it.
     *
     * @return int return the new key id (as int).
     * @param $accountid represents account id to add api key for
     * @param $accountid represents plan id to attach api key to
     * @throws DBException if db has problem with connection or
     *                     select query cannot be executed.
     **/

    public function create_new_key($accountid,$planid)
    {
        // notice about plans schema, notify it        
        try {
            
            $db = $this->db_connect();
            $planStmt = $db->prepare("SELECT COUNT(id) from {$this->plans_table} where id = ?");

            $result = $planStmt->execute(array($planid));
            
            if (!$result || $planStmt->fetchColumn() === 0)
            {
                $exception = new DBException(DBException::DB_ERR_SELECT);
                throw $exception;             
            }
            
            $accountStmt = $db->prepare("SELECT COUNT(id) from {$this->accounts_table} where id = ?");
            $result = $accountStmt->execute(array($accountid));
            
            if (!$result || $accountStmt->fetchColumn() === 0)
            {
                $exception = new DBException(DBException::DB_ERR_SELECT);
                throw $exception;             

            }
          
            $stmt = $db->prepare("INSERT INTO {$this->api_keys_table} (account_id, api_key, requests, plan_id, date, status) VALUES (:accountid, :apikey, :requests, :planid, :date, :status)");
            $data = [
                'accountid' => $accountid,
                'apikey' => $this->generate_apikey_string(), 
                'requests' => 0, // represent api key current requests, not the plan limit
                'planid' => $planid,
                'date' => time(),
                'status' => 'active'
               
            ];
            $result = $stmt->execute($data);

            if (!$result) {
                $exception = new DBException(DBException::DB_ERR_INSERT);
                throw $exception;             
            }

            $keyid = $db->lastInsertId();
            
            $this->regenerate_key($keyid);
            
            return $keyid;
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_INSERT,$e);
            throw $exception;             

            
        }
        
        $this->db_close_connection();        
        return NULL;
        
    }
    
    /**
     * gerenrates a new api key string.
     *
     * @return string represent api key string (random and unique id).
     **/

    public function generate_apikey_string () {

        do {
            $string = uniqid();
            $string = md5($string);
            
            try {
                $db = $this->db_connect();
                $stmt = $db->prepare("SELECT COUNT(api_key) FROM {$this->api_keys_table} WHERE api_key = :key");
                
                $data = array (
                    'key' => $string,
                );
                $result = $stmt->execute($data);
                
                if ($result === false) {
                    $exception = new DBException(DBException::DB_ERR_SELECT);
                    $exception->set_select_data("Error while ensuring api key uniqueness");
                    throw $exception;
                }
            }
            
            catch (PDOException $e) {
                $exception = new DBException(DBException::DB_ERR_SELECT,$e);
                $exception->set_select_data("Error while ensuring api key uniqueness in db");
                throw $exception;             
            }
            
            $matches = $stmt->fetchColumn();
            
        } while ($matches !== 0);
            
        return $string;
    }

    
    /**
     * revokes the old api key string, and replace it with a new string.
     *
     * @param $keyid key id to regenerate api string to.

     * @throws DBException if db connection encounters a problem or
     *                     or update statment failed.
     **/

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
            
            if (!$result) {
                $exception = new DBException(DBException::DB_ERR_UPDATE);
                throw $exception;
            }
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_UPDATE,$e);
            throw $exception;             
    
        }

        return $key;
    }
    
    /**
     * changes the account plan for the given api key.
     *
     * @return int Indicates the number of items.
     * @param $keyid represents key id to change plan for.
     * @param $targetplanid represents the new plan id.
     * @throws DBException if db connection encounter a problem or
     *                          the change process failed at some point.
     **/


    public function change_account_plan(int $keyid, int $targetplanid)
    {
        try {

            $db = $this->db_connect();

            $planStmt = $db->prepare("SELECT COUNT(id) from {$this->plans_table} where id = ?");
            $result = $planStmt->execute(array($targetplanid));
            
            if (!$result || $planStmt->fetchColumn() === 0)
            {
                $exception = new DBException(DBException::DB_ERR_SELECT);
                throw $exception;             
            }
            
            $stmt = $db->prepare("UPDATE {$this->api_keys_table} SET plan_id = :planid where id = :id");

            $data  = [
                'planid' => $targetplanid,
                'id'     => $keyid
            ];
        
            $result = $stmt->execute($data);
            
            if (!$result)
            {
                $exception = new DBException(DBException::DB_ERR_UPDATE);
                throw $exception;             
            
            }
    
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_UPDATE,$e);
            throw $exception;             
        }
    }

    
    /**
     * changes the plan name for the given api key.
     *
     * @return string represnets key's plan name.
     * @param int $keyid represents key id to get plan for.
     * @throws DBException if the db connection encounters a problem or
     *                     getting name process failed.
     **/

    public function get_plan_name($keyid) {
        try {
            $db = $this->db_connect();
            $keyStmt = $db->prepare("SELECT COUNT(id) from {$this->api_keys_table} where id = ?");

            $result = $keyStmt->execute(array($keyid));
            
            if (!$result || $keyStmt->fetchColumn() === 0)
            {
                $exception = new DBException(DBException::DB_ERR_SELECT);
                throw $exception;             
                
            }
            
            $cmpStmt = $db->prepare("SELECT {$this->plans_table}.name FROM {$this->api_keys_table}, {$this->plans_table} WHERE {$this->api_keys_table}.id = ? AND {$this->plans_table}.id = {$this->api_keys_table}.plan_id LIMIT 1");
            
            $cmpStmt->execute(array($keyid));

            if (!$result)
            {
                $exception = new DBException(DBException::DB_ERR_SELECT);
                throw $exception;             

            }
            $name = $cmpStmt->fetchColumn();

            $this->db_close_connection();
            return $name;
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_CONN_WRONG_DATA,$e);
            $exception->set_connection_wrong_data("Error while connecting to db, please check if server login credentials is correct.");
            throw $exception;             
        }
        
        $this->db_close_connection();
        return null;
    }


    /**
     * gets the api key expiry date.
     *
     * @return int indicates the expiry date (as a UNIX timestamp) .
     * @param int $keyid represents key id to get expirity time for.
     * @throws DBException if db connection problem encoutered a problem or 
     *                     getting expirity process failed.
     **/
        
    public function get_account_plan_expiry($keyid) { 

        $this->ensure_apikey_exists($keyid);
        
        try {
            $db = $this->db_connect();
            $subscriptionStmt = $db->prepare("SELECT {$this->api_subscriptions_table}.subscription_period, {$this->api_subscriptions_table}.date FROM {$this->api_subscriptions_table}, {$this->api_keys_table} WHERE api_key_id = :keyid AND {$this->api_keys_table}.status = :status ORDER BY {$this->api_subscriptions_table}.date DESC LIMIT 1"); // the latest subscription

            $data = array(
                'keyid' => $keyid,
                'status' => 'active'
            );
                
            $subscriptionStmt->execute($data);
            
            $subscription = $subscriptionStmt->fetch(PDO::FETCH_OBJ);
            
            if ($subscription === false)
            {
                $exception = new DBException(DBException::DB_ERR_SELECT);
                throw $exception;
            }

            $period = $subscription->subscription_period;
            $startdate = $subscription->date;
            $this->db_close_connection();

            return $startdate + $period; 
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_SELECT,$e);
            throw $exception;
        }

        $this->db_close_connection();
        return NULL;  
    }

    /**
     * gets the connection referrer.
     *
     * @return string represents referrer (as ip string or host name). 
     **/

    public function get_referrer()  {
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
               
        return $referrer;
    }
    /** 
     * gets the remaining api key requests number that it's allowed to use.
     *
     * @return int represent the number remaining reuqests for api key.
     * @param int $keyid represents the api key id to get the number of avaliable requests for.
     * @throws DBException if the db connection encountered a problem or
     *                     getting required info process from db has failed.
     **/
    
    public function get_remaining_quotas($keyid)
    {
        try {
            $db = $this->db_connect();
            
            $keyStmt = $db->prepare("SELECT requests,plan_id FROM {$this->api_keys_table} WHERE id = ? LIMIT 1"); 
            $keyStmt->execute(array($keyid));
            $key = $keyStmt->fetch(PDO::FETCH_OBJ);

            if (!$key)
            {
                $exception = new DBException(DBException::DB_ERR_SELECT);
                throw $exception;
            
            }           
        }
        
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_SELECT,$e);
            throw $exception;
        }
        
        $requests = $key->requests;
        $planid = $key->plan_id;

        try {
            $planStmt = $db->prepare("SELECT max_requests FROM {$this->plans_table} WHERE id = ?");
            $planStmt->execute(array($planid));
            $planrequests = $planStmt->fetchColumn();

            
            if ($planrequests === false)
            {

                $exception = new DBException(DBException::DB_ERR_SELECT);
                throw $exception;
            }

            $this->db_close_connection();
            return $planrequests - $requests < 0 ? 0 : $planrequests - $requests ;
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_SELECT,$e);
            throw $exception;
        }

        $this->db_close_connection();
        return null;
    }
    
    /**
     * ensures that apikey is in db.
     *
     * @return true if it in api_keys_table, otherwise will not return
     *                                       (you'll get an exception)
     * @param $keyid api key's id.
     * @throws DBException if db has problem with connection or
     *                     the query cannot be executed.
     * @throws Exception if didn't find $keyid in api_keys_table.
     *
     **/
    
    private function ensure_apikey_exists(int $keyid) {
        
        try {
            $db = $this->db_connect();
            
            $stmt = $db->prepare("SELECT COUNT(id) FROM {$this->api_keys_table} WHERE id = ?");
            
            $stmt->execute(array($keyid));
            $rows = $stmt->fetchColumn();
                
        if ($rows === 1) {
            $this->db_close_connection();
            return true;
        }
        }
        catch (PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_SELECT,$e);
            $exception->set_select_data( "Error while ensuring api key is in db");
            throw  $exception;
        }

        $this->db_close_connection();
        throw new Exception('No such Api Key',14);
    }
    
    /**
     * add an entry to api log.
     *
     * @return true if it in api_keys_table, otherwise will not return
     *                                       (you'll get an exception)
     * @param int $keyid api key's id.
     * @param string $value is the entry value, can be null but not empty string.
     * @param string $type represents the entry key.
     * @throws DBException if db has problem with connection or
     *                     the insert query cannot be executed.
     * @throws InvalidException if the $value or $type is empty string. 
     * 
     **/
      

    public function log_api_request(int $api_key_id, $type, $value = null) {
        
        $this->ensure_apikey_exists($api_key_id);
        
        if (empty($type)) {
            throw new InvalidArgumentException("Log type is empty",6);
        }
        
        if ($value !== null && empty($value)) {
            throw new InvalidArgumentException("Log value is empty string",7);
        }
        
        $referrer = $this->get_referrer();
        try {
            $db = $this->db_connect();

            $stmt = $db->prepare("INSERT INTO {$this->requests_info_table} (type,value, api_key_id, referrer,time) VALUES (:type, :value,:api_key_id, :referrer, :time)");
          
            $data = [
                'type' => $type,
                'value'    => $value,
                'api_key_id'     => $api_key_id,
                'referrer'    => $referrer,
                'time'        =>  time()   
                
            ];
            
            $result = $stmt->execute($data);

            if ($result === false || $stmt->rowCount() !== 1) {
                $exception = new DBException(DBException::DB_ERR_INSERT);
                $exception->set_insert_data("Error while adding a new request log");
                $this->db_close_connection();
                throw $exception;
            }                
        }
        
        catch (PDOException $e) {

            $exception = new DBException(DBException::DB_ERR_INSERT);
            throw $exception;
        }
        
        $this->db_close_connection();

    }
}

?>
