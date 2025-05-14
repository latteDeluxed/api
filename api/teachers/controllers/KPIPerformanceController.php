<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/KPIPerformanceModel.php';

class KPIPerformanceController extends Controller {
    public function __construct($db) {
        parent::__construct(KPIPerformanceModel::class, $db);
    }
}
