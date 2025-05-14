<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/NotificationsModel.php';

class NotificationsController extends Controller {
    public function __construct($db) {
        parent::__construct(NotificationsModel::class, $db);
    }
}
