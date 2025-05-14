<?php
require_once __DIR__ . '/../core/Model.php';

class StudentHealthRecordsModel extends Model {
    public $table = 'StudentHealthRecords';
    public $primaryKey = 'studenthealthrecords_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
