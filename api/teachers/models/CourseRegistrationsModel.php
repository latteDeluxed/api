<?php
require_once __DIR__ . '/../core/Model.php';

class CourseRegistrationsModel extends Model {
    public $table = 'CourseRegistrations';
    public $primaryKey = 'courseregistrations_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
