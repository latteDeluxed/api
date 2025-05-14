<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/AttendanceModel.php';

class AttendanceController extends Controller {
    public function __construct($db) {
        parent::__construct(AttendanceModel::class, $db);
    }
}
