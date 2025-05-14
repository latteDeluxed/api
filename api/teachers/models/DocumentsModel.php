<?php
require_once __DIR__ . '/../core/Model.php';

class DocumentsModel extends Model {
    public $table = 'Documents';
    public $primaryKey = 'documents_id';

    public function __construct($db) {
        parent::__construct($db);
    }
}
