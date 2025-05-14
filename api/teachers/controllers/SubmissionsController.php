<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/SubmissionsModel.php';

class SubmissionsController extends Controller {
    public function __construct($db) {
        parent::__construct(SubmissionsModel::class, $db);
    }
}
