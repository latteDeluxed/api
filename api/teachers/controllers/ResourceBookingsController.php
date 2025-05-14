<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ResourceBookingsModel.php';

class ResourceBookingsController extends Controller {
    public function __construct($db) {
        parent::__construct(ResourceBookingsModel::class, $db);
    }
}
