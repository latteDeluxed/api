<?php
require_once __DIR__ . '/../core/Model.php';

class KPIPerformanceModel extends Model {
    public $table = 'KPIPerformance';
    public $primaryKey = 'kpiperformance_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
