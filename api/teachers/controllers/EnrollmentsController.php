<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EnrollmentsModel.php';

class EnrollmentsController extends Controller {
    public function __construct($db) {
        parent::__construct(EnrollmentsModel::class, $db);
    }
}
