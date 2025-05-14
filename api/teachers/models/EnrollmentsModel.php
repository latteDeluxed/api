<?php
require_once __DIR__ . '/../core/Model.php';

class EnrollmentsModel extends Model {
    public $table = 'Enrollments';
    public $primaryKey = 'enrollments_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
