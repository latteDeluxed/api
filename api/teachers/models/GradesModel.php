<?php
require_once __DIR__ . '/../core/Model.php';

class GradesModel extends Model {
    public $table = 'Grades';
    public $primaryKey = 'grades_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
