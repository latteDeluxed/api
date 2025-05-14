<?php
// core/Model.php

class Model {
    /**
     * PDO connection object
     * @var \PDO
     */
    public $conn;

    /**
     * Database table name
     * @var string
     */
    public $table;

    /**
     * Primary key column name
     * @var string
     */
    public $primaryKey;

    /**
     * Fields allowed for insert/update operations
     * @var array
     */
    protected $allowedFields = [];

    /**
     * Constructor
     * @param \PDO $db
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all records from the table
     * @return array
     */
    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get a single record by primary key
     * @param mixed $id
     * @return array|false
     */
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Insert a new record
     * @param array $data
     * @return array|null
     */
    public function create($data) {
        // Filter allowed fields
        $fields = array_intersect_key($data, array_flip($this->allowedFields));
        $columns = implode(',', array_keys($fields));
        $placeholders = ':' . implode(',:', array_keys($fields));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->conn->prepare($sql);
        foreach ($fields as $col => $val) {
            $stmt->bindValue(':' . $col, $val);
        }
        if ($stmt->execute()) {
            $id = $this->conn->lastInsertId();
            return $this->getById($id);
        }
        return null;
    }

    /**
     * Update an existing record
     * @param mixed $id
     * @param array $data
     * @return array|null
     */
    public function update($id, $data) {
        $fields = array_intersect_key($data, array_flip($this->allowedFields));
        $setClause = implode(', ', array_map(fn($col) => "{$col} = :{$col}", array_keys($fields)));
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        $stmt = $this->conn->prepare($sql);
        foreach ($fields as $col => $val) {
            $stmt->bindValue(':' . $col, $val);
        }
        $stmt->bindValue(':id', $id);
        if ($stmt->execute()) {
            return $this->getById($id);
        }
        return null;
    }

    /**
     * Delete a record by primary key
     * @param mixed $id
     * @return bool
     */
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
