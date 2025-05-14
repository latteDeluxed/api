<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/CourseRegistrationsModel.php';

class CourseRegistrationsController extends Controller {
    public function __construct($db) {
        parent::__construct(CourseRegistrationsModel::class, $db);
    }
}
