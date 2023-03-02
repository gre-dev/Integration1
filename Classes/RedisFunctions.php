<?php

class RedisFunctions
{
    private $redishost;
    private $redisport;
    private $redis;
    private  $pdo;

    /**
     * @throws DBException
     */
    function __construct()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
        $dotenv->load();
        $dotenv->required(['REDIS_HOST', 'REDIS_PORT']);

//        $this->dbhost = $_ENV['DB_HOST'];
//        $this->dbname = $_ENV['DB_NAME'];
//        $this->dbuser = $_ENV['DB_USER'];
//        $this->dbpass = $_ENV['DB_PASSWORD'];
        $this->redishost = $_ENV['REDIS_HOST'];
        $this->redisport = $_ENV['REDIS_PORT'];

        // Connect to Redis
        try {
            $this->redis = new Redis();
            $this->redis->connect($this->redishost, $this->redisport);
        }catch (RedisException $e) {
            echo "Could not connect to Redis: " . $e->getMessage();
        }

//        // Connect to MySQL
//        try {
//            $this->pdo = new PDO("mysql:host={$this->dbhost};dbname={$this->dbname}", $this->dbuser, $this->dbpass);
//            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
//            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//        }
//        catch(PDOException $e) {
//            $exception = new DBException(DBException::DB_ERR_CONN_WRONG_DATA,$e);
//            $exception->set_connection_wrong_data("Error while connecting to db, please check if server login credentials is correct.");
//            throw $exception;
//        }
    }

    public function query($conn,$query, $params = [])
    {
        // Connect to MySQL
        $this->pdo = $conn;
        // Check if the query is already cached
        $redis_key = md5($query.serialize($params));
        $data = $this->redis->get($redis_key);

        if ($data && strval($data) !== '0') {
            return json_decode($data);
        }

        // Query the database
        $stmt = $this->pdo->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //store the result in redis
        $this->redis->set($redis_key, json_encode($result));

        return $result;

    }
}