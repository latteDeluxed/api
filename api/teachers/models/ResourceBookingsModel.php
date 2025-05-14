<?php
require_once __DIR__ . '/../core/Model.php';

class ResourceBookingsModel extends Model {
    public $table = 'ResourceBookings';
    public $primaryKey = 'resourcebookings_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
