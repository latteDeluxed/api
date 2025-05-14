<?php
require_once __DIR__ . '/../core/Model.php';

class CoursesModelModel extends Model {
    public $table = 'Courses';
    public $primaryKey = 'course_id';
    protected $allowedFields = ['course_code', 'course_name', 'description', 'credits', 'department', 'created_at', 'updated_at'];
}
