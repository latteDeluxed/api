<?php
require_once __DIR__ . '/../core/Model.php';

class TeachersModel extends Model {
    public $table = 'Teachers';
    public $primaryKey = 'teachers_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
