<?php
// config/database.php
// Configuración de base de datos
// ✅ FIX: catch (Throwable) en isConnected()

class Database {
    private static $instance = null;
    private $connection;

    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset = 'utf8mb4';

    private function __construct() {
        $this->host = 'localhost';
        $this->dbname = 'mantenimiento_db';
        $this->username = 'root';
        $this->password = '';

        if (isset($_ENV['DB_HOST']))     $this->host     = $_ENV['DB_HOST'];
        if (isset($_ENV['DB_NAME']))     $this->dbname   = $_ENV['DB_NAME'];
        if (isset($_ENV['DB_USER']))     $this->username = $_ENV['DB_USER'];
        if (isset($_ENV['DB_PASSWORD'])) $this->password = $_ENV['DB_PASSWORD'];

        $this->connect();
    }

    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $this->connection = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            die("Error de conexión a la base de datos. Contacte al administrador.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function isConnected() {
        try {
            return $this->connection !== null;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getDatabaseName() {
        return $this->dbname;
    }

    public function close() {
        $this->connection = null;
        self::$instance = null;
    }

    private function __clone() {}
    public function __wakeup() {}
}