<?php
require_once __DIR__ . '/../core/Model.php';

class NotificationsModel extends Model {
    public $table = 'Notifications';
    public $primaryKey = 'notifications_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
