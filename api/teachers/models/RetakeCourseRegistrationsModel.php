<?php
require_once __DIR__ . '/../core/Model.php';

class RetakeCourseRegistrationsModel extends Model {
    public $table = 'RetakeCourseRegistrations';
    public $primaryKey = 'retakecourseregistrations_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
