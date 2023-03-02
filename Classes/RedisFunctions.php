<?php

class RedisFunctions
{
    /**
     * @var string $dbhost mysql db host name or ip to use when quering data.
     * @var string $dbname mysql db name to get data from.
     * @var string $dbuser mysql db user to use.
     * @var string $dbpass mysql db password to use
     **/
    private $dbhost;
    private $dbname;
    private $dbuser;
    private $dbpass;
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
        $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'REDIS_HOST', 'REDIS_PORT']);

        $this->dbhost = $_ENV['DB_HOST'];
        $this->dbname = $_ENV['DB_NAME'];
        $this->dbuser = $_ENV['DB_USER'];
        $this->dbpass = $_ENV['DB_PASSWORD'];
        $this->redishost = $_ENV['REDIS_HOST'];
        $this->redisport = $_ENV['REDIS_PORT'];

        // Connect to Redis
        try {
            $this->redis = new Redis();
            $this->redis->connect($this->redishost, $this->redisport);
        }catch (RedisException $e) {
            echo "Could not connect to Redis: " . $e->getMessage();
        }

        // Connect to MySQL
        try {
            $this->pdo = new PDO("mysql:host={$this->dbhost};dbname={$this->dbname}", $this->dbuser, $this->dbpass);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e) {
            $exception = new DBException(DBException::DB_ERR_CONN_WRONG_DATA,$e);
            $exception->set_connection_wrong_data("Error while connecting to db, please check if server login credentials is correct.");
            throw $exception;
        }
    }
}