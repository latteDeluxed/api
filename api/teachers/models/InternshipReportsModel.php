<?php
require_once __DIR__ . '/../core/Model.php';

class InternshipReportsModel extends Model {
    public $table = 'InternshipReports';
    public $primaryKey = 'internshipreports_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
