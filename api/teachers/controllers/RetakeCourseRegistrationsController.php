<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/RetakeCourseRegistrationsModel.php';

class RetakeCourseRegistrationsController extends Controller {
    public function __construct($db) {
        parent::__construct(RetakeCourseRegistrationsModel::class, $db);
    }
}
