<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/SchoolResourcesModel.php';

class SchoolResourcesController extends Controller {
    public function __construct($db) {
        parent::__construct(SchoolResourcesModel::class, $db);
    }
}
