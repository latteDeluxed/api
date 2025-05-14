<?php
require_once __DIR__ . '/../core/Model.php';

class TeacherAssignmentsModel extends Model {
    public $table = 'TeacherAssignments';
    public $primaryKey = 'teacherassignments_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
