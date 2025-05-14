<?php
require_once __DIR__ . '/../core/Model.php';

class QualityAssessmentResultsModel extends Model {
    public $table = 'QualityAssessmentResults';
    public $primaryKey = 'qualityassessmentresults_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
