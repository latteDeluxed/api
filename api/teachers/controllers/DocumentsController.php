<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/DocumentsModel.php';

class DocumentsController extends Controller {
    public function __construct($db) {
        parent::__construct(DocumentsModel::class, $db);
    }
}
