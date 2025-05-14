<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/InternshipRegistrationsModel.php';

class InternshipRegistrationsController extends Controller {
    public function __construct($db) {
        parent::__construct(InternshipRegistrationsModel::class, $db);
    }
}
