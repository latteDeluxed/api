<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/TeachersModel.php';

class TeachersController extends Controller {
    public function __construct($db) {
        parent::__construct(TeachersModel::class, $db);
    }
}
