<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/StudentHealthRecordsModel.php';

class StudentHealthRecordsController extends Controller {
    public function __construct($db) {
        parent::__construct(StudentHealthRecordsModel::class, $db);
    }
}
