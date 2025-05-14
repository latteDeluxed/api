<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/InternshipReportsModel.php';

class InternshipReportsController extends Controller {
    public function __construct($db) {
        parent::__construct(InternshipReportsModel::class, $db);
    }
}
