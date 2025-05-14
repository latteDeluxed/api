<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/CourseMaterialsModel.php';

class CourseMaterialsController extends Controller {
    public function __construct($db) {
        parent::__construct(CourseMaterialsModel::class, $db);
    }
}
