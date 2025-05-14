<?php
class Controller {
    protected $model;
    protected $db;

    public function __construct($modelClass, $db) {
        $this->db = $db;
        $this->model = new $modelClass($db);
    }

    public function getAll() {
        $stmt = $this->model->conn->prepare("SELECT * FROM {$this->model->table}");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getById($id) {
        $stmt = $this->model->conn->prepare("SELECT * FROM {$this->model->table} WHERE {$this->model->primaryKey} = ?");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function create($data) {
        $fields = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $stmt = $this->model->conn->prepare("INSERT INTO {$this->model->table} (" . implode(',', $fields) . ") VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        echo json_encode(['insert_id' => $this->model->conn->lastInsertId()]);
    }

    public function update($id, $data) {
        $fields = array_keys($data);
        $assignments = implode('=?,', $fields) . '=?';
        $stmt = $this->model->conn->prepare("UPDATE {$this->model->table} SET $assignments WHERE {$this->model->primaryKey} = ?");
        $stmt->execute(array_merge(array_values($data), [$id]));
        echo json_encode(['updated' => $stmt->rowCount()]);
    }

    public function delete($id) {
        $stmt = $this->model->conn->prepare("DELETE FROM {$this->model->table} WHERE {$this->model->primaryKey} = ?");
        $stmt->execute([$id]);
        echo json_encode(['deleted' => $stmt->rowCount()]);
    }
}
