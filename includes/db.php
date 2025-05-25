<?php
class Database {
    private static $instance = null;
    private $connection;
    private $queryCount = 0;
    private $queryLog = [];
    
    private function __construct() {
        try {
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset
            if (!$this->connection->set_charset(DB_CHARSET)) {
                throw new Exception("Error setting charset: " . $this->connection->error);
            }
            
            // Set timezone
            $timezone = date('P');
            $this->connection->query("SET time_zone = '$timezone'");
            
        } catch (Exception $e) {
            $this->logError($e);
            die("Database connection error. Please try again later.");
        }
    }
    
    // Prevent cloning of the instance
    private function __clone() {}
    
    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the database connection
     */
    public function getConnection(): mysqli {
        return $this->connection;
    }
    
    /**
     * Execute a query with prepared statements
     * 
     * @param string $sql The SQL query
     * @param array $params Parameters for prepared statement
     * @return mysqli_stmt|false
     */
    public function query(string $sql, array $params = []) {
        try {
            $this->queryCount++;
            $startTime = microtime(true);
            
            $stmt = $this->connection->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            if (!empty($params)) {
                $types = '';
                $bindParams = [];
                
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                    $bindParams[] = $param;
                }
                
                array_unshift($bindParams, $types);
                
                if (!call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams))) {
                    throw new Exception("Binding parameters failed: " . $stmt->error);
                }
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            // Log query for debugging
            if (DEBUG_MODE) {
                $endTime = microtime(true);
                $this->logQuery($sql, $params, $endTime - $startTime);
            }
            
            return $stmt;
            
        } catch (Exception $e) {
            $this->logError($e);
            throw $e;
        }
    }
    
    /**
     * Get a single row
     * 
     * @param string $sql The SQL query
     * @param array $params Parameters for prepared statement
     * @return array|null
     */
    public function getRow(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Exception $e) {
            $this->logError($e);
            throw $e;
        }
    }
    
    /**
     * Get multiple rows
     * 
     * @param string $sql The SQL query
     * @param array $params Parameters for prepared statement
     * @return array
     */
    public function getRows(string $sql, array $params = []): array {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->get_result();
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Exception $e) {
            $this->logError($e);
            throw $e;
        }
    }
    
    /**
     * Get a single value
     * 
     * @param string $sql The SQL query
     * @param array $params Parameters for prepared statement
     * @return mixed
     */
    public function getValue(string $sql, array $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->get_result();
            $row = $result->fetch_array(MYSQLI_NUM);
            $stmt->close();
            return $row ? $row[0] : null;
        } catch (Exception $e) {
            $this->logError($e);
            throw $e;
        }
    }
    
    /**
     * Insert a row and return the insert ID
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|string
     */
    public function insert(string $table, array $data) {
        try {
            $columns = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO $table ($columns) VALUES ($values)";
            
            $stmt = $this->query($sql, array_values($data));
            $insertId = $this->connection->insert_id;
            $stmt->close();
            
            return $insertId;
        } catch (Exception $e) {
            $this->logError($e);
            throw $e;
        }
    }
    
    /**
     * Update rows
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $params = []): int {
        try {
            $set = implode(' = ?, ', array_keys($data)) . ' = ?';
            $sql = "UPDATE $table SET $set WHERE $where";
            
            $stmt = $this->query($sql, array_merge(array_values($data), $params));
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return $affectedRows;
        } catch (Exception $e) {
            $this->logError($e);
            throw $e;
        }
    }
    
    /**
     * Delete rows
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete(string $table, string $where, array $params = []): int {
        try {
            $sql = "DELETE FROM $table WHERE $where";
            
            $stmt = $this->query($sql, $params);
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return $affectedRows;
        } catch (Exception $e) {
            $this->logError($e);
            throw $e;
        }
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction(): void {
        $this->connection->begin_transaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit(): void {
        $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback(): void {
        $this->connection->rollback();
    }
    
    /**
     * Get the number of queries executed
     */
    public function getQueryCount(): int {
        return $this->queryCount;
    }
    
    /**
     * Get the query log
     */
    public function getQueryLog(): array {
        return $this->queryLog;
    }
    
    /**
     * Convert array values to references for bind_param
     */
    private function refValues(array &$arr): array {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
    
    /**
     * Log a query for debugging
     */
    private function logQuery(string $sql, array $params, float $duration): void {
        $this->queryLog[] = [
            'sql' => $sql,
            'params' => $params,
            'duration' => $duration,
            'time' => date('Y-m-d H:i:s'),
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];
    }
    
    /**
     * Log database errors
     */
    private function logError(Exception $e): void {
        $logFile = BASE_PATH . '/logs/db_error.log';
        $timestamp = date('Y-m-d H:i:s');
        $message = sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s\n",
            $timestamp,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        error_log($message, 3, $logFile);
    }
    
    /**
     * Clean up on destruction
     */
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
