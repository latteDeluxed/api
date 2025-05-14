<?php
require_once __DIR__ . '/../core/Model.php';

class OnlineTestsModelModel extends Model {
    public $table = 'OnlineTests';
    public $primaryKey = 'test_id';
    protected $allowedFields = ['test_name', 'course_id', 'scheduled_date', 'duration', 'max_score', 'created_by'];
}
