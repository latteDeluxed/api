<?php
require_once __DIR__ . '/../core/Model.php';

class SurveysModelModel extends Model {
    public $table = 'Surveys';
    public $primaryKey = 'survey_id';
    protected $allowedFields = ['title', 'description', 'course_id', 'created_by', 'created_at'];
}
