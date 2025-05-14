<?php
require_once __DIR__ . '/../core/Model.php';

class CourseMaterialsModel extends Model {
    public $table = 'CourseMaterials';
    public $primaryKey = 'coursematerials_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
