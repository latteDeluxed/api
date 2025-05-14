<?php
require_once __DIR__ . '/../core/Model.php';

class SchoolResourcesModel extends Model {
    public $table = 'SchoolResources';
    public $primaryKey = 'schoolresources_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
