<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EventRegistrationsModel.php';

class EventRegistrationsController extends Controller {
    public function __construct($db) {
        parent::__construct(EventRegistrationsModel::class, $db);
    }
}
