<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/RetakeExamRegistrationsModel.php';

class RetakeExamRegistrationsController extends Controller {
    public function __construct($db) {
        parent::__construct(RetakeExamRegistrationsModel::class, $db);
    }
}
