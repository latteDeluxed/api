<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/GradesModel.php';

class GradesController extends Controller {
    public function __construct($db) {
        parent::__construct(GradesModel::class, $db);
    }
}
