<?php
require_once __DIR__ . '/../core/Model.php';

class InternshipRegistrationsModel extends Model {
    public $table = 'InternshipRegistrations';
    public $primaryKey = 'internshipregistrations_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
