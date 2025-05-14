<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/TeachingScheduleModel.php';

class TeachingScheduleController extends Controller {
    public function __construct($db) {
        parent::__construct(TeachingScheduleModel::class, $db);
    }
}
