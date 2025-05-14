<?php
require_once __DIR__ . '/../core/Model.php';

class RetakeExamRegistrationsModel extends Model {
    public $table = 'RetakeExamRegistrations';
    public $primaryKey = 'retakeexamregistrations_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
