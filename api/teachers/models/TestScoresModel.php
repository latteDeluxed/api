<?php
require_once __DIR__ . '/../core/Model.php';

class TestScoresModel extends Model {
    public $table = 'TestScores';
    public $primaryKey = 'testscores_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
