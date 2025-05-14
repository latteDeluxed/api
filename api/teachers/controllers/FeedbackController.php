<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/FeedbackModel.php';

class FeedbackController extends Controller {
    public function __construct($db) {
        parent::__construct(FeedbackModel::class, $db);
    }
}
