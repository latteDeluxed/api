<?php
require_once __DIR__ . '/../core/Model.php';

class SubmissionsModel extends Model {
    public $table = 'Submissions';
    public $primaryKey = 'submissions_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
