<?php
require_once __DIR__ . '/../core/Model.php';

class FeedbackModel extends Model {
    public $table = 'Feedback';
    public $primaryKey = 'feedback_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
