<?php
require_once __DIR__ . '/../core/Model.php';

class TeacherQualificationsModel extends Model {
    public $table = 'TeacherQualifications';
    public $primaryKey = 'teacherqualifications_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
