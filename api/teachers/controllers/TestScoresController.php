<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/TestScoresModel.php';

class TestScoresController extends Controller {
    public function __construct($db) {
        parent::__construct(TestScoresModel::class, $db);
    }
}
