<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/TeacherQualificationsModel.php';

class TeacherQualificationsController extends Controller {
    public function __construct($db) {
        parent::__construct(TeacherQualificationsModel::class, $db);
    }
}
