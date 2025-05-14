<?php
// index.php
// Entry point and router for edited_php_rest_api

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Base project directory and config
$baseDir = __DIR__;
require_once $baseDir . '/config/database.php';
require_once $baseDir . '/core/Controller.php';

// Autoload controllers and models
spl_autoload_register(function($class) {
    $paths = [__DIR__ . '/controllers/', __DIR__ . '/models/'];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Initialize database
$database = new Database();
$db = $database->getConnection();

// Parse request URI and method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove base path prefix (project folder name)
$basePath = '/teachers';
$path = substr($requestUri, strlen($basePath));
if ($path === false || $path === '') {
    $path = '/';
}

// Define resources and corresponding controller names
$resources = [
    'teachers'                  => 'TeachersController',
    'teacherqualifications'     => 'TeacherQualificationsController',
    'teacherassignments'        => 'TeacherAssignmentsController',
    'teachingschedule'          => 'TeachingScheduleController',
    'documents'                 => 'DocumentsController',
    'coursematerials'           => 'CourseMaterialsController',
    'kpiperformance'            => 'KPIPerformanceController',
    'qualityassessmentresults'  => 'QualityAssessmentResultsController',
    'schoolresources'           => 'SchoolResourcesController',
    'resourcebookings'          => 'ResourceBookingsController',
    'courses'                   => 'CoursesController',
    'onlinetests'               => 'OnlineTestsController',
    'surveys'                   => 'SurveysController',
    'courseregistrations'       => 'CourseRegistrationsController',
    'enrollments'               => 'EnrollmentsController',
    'attendance'                => 'AttendanceController',
    'submissions'               => 'SubmissionsController',
    'grades'                    => 'GradesController',
    'testscores'                => 'TestScoresController',
    'retakeexamregistrations'   => 'RetakeExamRegistrationsController',
    'retakecourseregistrations' => 'RetakeCourseRegistrationsController',
    'internshipregistrations'   => 'InternshipRegistrationsController',
    'internshipreports'         => 'InternshipReportsController',
    'notifications'             => 'NotificationsController',
    'feedback'                  => 'FeedbackController',
    'eventregistrations'        => 'EventRegistrationsController',
    'studenthealthrecords'      => 'StudentHealthRecordsController'
];

// Routing
foreach ($resources as $route => $controllerName) {
    // List all or create new
    if (preg_match("#^/{$route}$#", $path)) {
        $controller = new $controllerName($db);
        switch ($requestMethod) {
            case 'GET':
                // Original controllers use getAll()
                $controller->getAll();
                exit;
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $controller->create($data);
                exit;
        }
    }
    // Show, update or delete by ID
    if (preg_match("#^/{$route}/(\d+)$#", $path, $matches)) {
        $controller = new $controllerName($db);
        $id = $matches[1];
        switch ($requestMethod) {
            case 'GET':
                // Original controllers use getById()
                $controller->getById($id);
                exit;
            case 'PUT':
            case 'PATCH':
                $data = json_decode(file_get_contents('php://input'), true);
                $controller->update($id, $data);
                exit;
            case 'DELETE':
                $controller->delete($id);
                exit;
        }
    }
}

// If no route matched, return 404
header('Content-Type: application/json');
http_response_code(404);
echo json_encode(['message' => 'Endpoint not found.']);
