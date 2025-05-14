<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/SurveysModel.php';

class SurveysController extends Controller {
    private $model;

    public function __construct($db) {
        $this->model = new SurveysModel($db);
    }

    public function index() {
        $results = $this->model->getAll();
        $this->sendResponse($results);
    }

    public function show($id) {
        $result = $this->model->getById($id);
        if ($result) {
            $this->sendResponse($result);
        } else {
            $this->sendError(404, 'Surveys not found.');
        }
    }

    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        $inserted = $this->model->create($data);
        $this->sendResponse($inserted, 201);
    }

    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $updated = $this->model->update($id, $data);
        $this->sendResponse($updated);
    }

    public function delete($id) {
        $this->model->delete($id);
        $this->sendResponse(['message' => 'Surveys deleted.']);
    }
}
