<?php
require_once __DIR__ . '/../core/Model.php';

class EventRegistrationsModel extends Model {
    public $table = 'EventRegistrations';
    public $primaryKey = 'eventregistrations_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
