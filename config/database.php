<?php
// config/database.php
// Configuración de base de datos - VERSIÓN CORREGIDA

class Database {
    private static $instance = null;
    private $connection;
    
    // Configuración
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    
    private function __construct() {
        // Valores por defecto para XAMPP
        $this->host = 'localhost';
        $this->dbname = 'mantenimiento_db';
        $this->username = 'root';
        $this->password = '';
        
        // Si existen variables de entorno, usarlas
        if (isset($_ENV['DB_HOST'])) {
            $this->host = $_ENV['DB_HOST'];
        }
        if (isset($_ENV['DB_NAME'])) {
            $this->dbname = $_ENV['DB_NAME'];
        }
        if (isset($_ENV['DB_USER'])) {
            $this->username = $_ENV['DB_USER'];
        }
        if (isset($_ENV['DB_PASSWORD'])) {
            $this->password = $_ENV['DB_PASSWORD'];
        }
        
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
    
    /**
     * Verificar conexión
     */
    public function isConnected() {
        try {
            return $this->connection !== null;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener el nombre de la base de datos
     */
    public function getDatabaseName() {
        return $this->dbname;
    }
    
    /**
     * Cerrar conexión
     */
    public function close() {
        $this->connection = null;
        self::$instance = null;
    }
    
    // Prevenir clonación
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {}
}
?>