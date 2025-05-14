<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/QualityAssessmentResultsModel.php';

class QualityAssessmentResultsController extends Controller {
    public function __construct($db) {
        parent::__construct(QualityAssessmentResultsModel::class, $db);
    }
}
