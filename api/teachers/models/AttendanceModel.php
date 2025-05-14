<?php
require_once __DIR__ . '/../core/Model.php';

class AttendanceModel extends Model {
    public $table = 'Attendance';
    public $primaryKey = 'attendance_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
