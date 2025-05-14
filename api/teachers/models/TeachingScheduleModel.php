<?php
require_once __DIR__ . '/../core/Model.php';

class TeachingScheduleModel extends Model {
    public $table = 'TeachingSchedule';
    public $primaryKey = 'teachingschedule_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
