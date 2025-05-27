<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tài liệu API Hệ thống Quản lý Sinh viên</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            line-height: 1.6; 
            background-color: #f9f9f9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #2c3e50; 
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            background: #ecf0f1;
            padding: 10px;
            border-left: 4px solid #3498db;
            margin-top: 30px;
        }
        code { 
            background: #f8f9fa; 
            padding: 2px 6px; 
            border-radius: 3px;
            color: #e74c3c;
            font-weight: bold;
        }
        .endpoint { 
            margin-bottom: 25px; 
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background: #fdfdfd;
        }
        .endpoint h3 {
            color: #2980b9;
            margin-top: 0;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 14px;
        }
        .method-get { border-left: 4px solid #27ae60; }
        .method-post { border-left: 4px solid #f39c12; }
        .method-put { border-left: 4px solid #8e44ad; }
        .method-delete { border-left: 4px solid #e74c3c; }
        .note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tài liệu API Hệ thống Quản lý Sinh viên</h1>
        
        <h2>1. API Sinh viên (Students)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /students.php</h3>
            <p>Lấy danh sách tất cả sinh viên trong hệ thống.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /students.php?student_id=<code>X</code></h3>
            <p>Lấy thông tin chi tiết của một sinh viên theo <code>student_id</code>.</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /students.php</h3>
            <p>Tạo mới một sinh viên. Dữ liệu JSON yêu cầu:</p>
<pre><code>{
    "student_code": "SV123456",
    "full_name": "Nguyễn Văn An",
    "email": "nguyenvanan@example.com",
    "phone_number": "0123456789", 
    "date_of_birth": "2000-01-01",
    "gender": "Male",
    "address": "123 Đường ABC, Hà Nội",
    "major": "Khoa học máy tính",
    "academic_year": 2023
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /students.php?student_id=<code>X</code></h3>
            <p>Cập nhật thông tin sinh viên. Dữ liệu JSON (có thể cập nhật từng phần):</p>
<pre><code>{
    "email": "email_moi@example.com",
    "phone_number": "0987654321",
    "address": "Địa chỉ mới"
}</code></pre>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /students.php?student_id=<code>X</code></h3>
            <p>Xóa sinh viên có <code>student_id</code> được chỉ định.</p>
        </div>

        <h2>2. API Khóa học (Courses)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /courses.php</h3>
            <p>Lấy danh sách tất cả các khóa học.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /courses.php?course_id=<code>X</code></h3>
            <p>Lấy thông tin chi tiết của một khóa học theo <code>course_id</code>.</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /courses.php</h3>
            <p>Tạo khóa học mới. Dữ liệu JSON yêu cầu:</p>
<pre><code>{
    "course_code": "CS101",
    "course_name": "Lập trình cơ bản",
    "credits": 3,
    "description": "Khóa học giới thiệu về lập trình",
    "semester": 1,
    "academic_year": 2023
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /courses.php?course_id=<code>X</code></h3>
            <p>Cập nhật thông tin khóa học.</p>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /courses.php?course_id=<code>X</code></h3>
            <p>Xóa khóa học có <code>course_id</code> được chỉ định.</p>
        </div>

        <h2>3. API Đăng ký khóa học (Course Registrations)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /registrations.php</h3>
            <p>Lấy danh sách tất cả các đăng ký khóa học.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /registrations.php?registration_id=<code>X</code></h3>
            <p>Lấy thông tin chi tiết của một đăng ký theo <code>registration_id</code>.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /registrations.php?student_id=<code>X</code></h3>
            <p>Lấy tất cả đăng ký của một sinh viên.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /registrations.php?course_id=<code>X</code></h3>
            <p>Lấy tất cả đăng ký cho một khóa học.</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /registrations.php</h3>
            <p>Tạo đăng ký khóa học mới. Dữ liệu JSON yêu cầu:</p>
<pre><code>{
    "student_id": 1,
    "course_id": 2,
    "status": "Pending"
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /registrations.php?registration_id=<code>X</code></h3>
            <p>Cập nhật trạng thái đăng ký (Pending/Approved/Rejected).</p>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /registrations.php?registration_id=<code>X</code></h3>
            <p>Xóa đăng ký khóa học.</p>
        </div>

        <h2>4. API Ghi danh (Enrollments)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /enrollments.php</h3>
            <p>Lấy danh sách tất cả các ghi danh.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /enrollments.php?enrollment_id=<code>X</code></h3>
            <p>Lấy thông tin chi tiết của một ghi danh.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /enrollments.php?registration_id=<code>X</code></h3>
            <p>Lấy ghi danh theo ID đăng ký.</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /enrollments.php</h3>
            <p>Tạo ghi danh mới từ đăng ký đã được duyệt:</p>
<pre><code>{
    "registration_id": 1,
    "status": "Active"
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /enrollments.php?enrollment_id=<code>X</code></h3>
            <p>Cập nhật trạng thái ghi danh (Active/Completed/Dropped).</p>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /enrollments.php?enrollment_id=<code>X</code></h3>
            <p>Xóa ghi danh.</p>
        </div>

        <h2>5. API Điểm danh (Attendance)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /attendance.php?attendance_id=<code>X</code></h3>
            <p>Lấy thông tin một bản ghi điểm danh.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /attendance.php?enrollment_id=<code>X</code></h3>
            <p>Lấy tất cả bản ghi điểm danh của một ghi danh.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /attendance.php?enrollment_id=<code>X</code>&start_date=<code>YYYY-MM-DD</code>&end_date=<code>YYYY-MM-DD</code></h3>
            <p>Lấy bản ghi điểm danh trong khoảng thời gian.</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /attendance.php</h3>
            <p>Tạo bản ghi điểm danh mới:</p>
<pre><code>{
    "enrollment_id": 1,
    "date": "2023-12-01",
    "status": "Present",
    "notes": "Ghi chú (tùy chọn)"
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /attendance.php?attendance_id=<code>X</code></h3>
            <p>Cập nhật bản ghi điểm danh.</p>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /attendance.php?attendance_id=<code>X</code></h3>
            <p>Xóa bản ghi điểm danh.</p>
        </div>

        <h2>6. API Bài tập (Assignments)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /assignments.php</h3>
            <p>Lấy danh sách tất cả bài tập.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /assignments.php?assignment_id=<code>X</code></h3>
            <p>Lấy thông tin chi tiết một bài tập.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /assignments.php?course_id=<code>X</code></h3>
            <p>Lấy tất cả bài tập của một khóa học.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /assignments.php?course_id=<code>X</code>&upcoming</h3>
            <p>Lấy các bài tập sắp tới của khóa học (hạn nộp >= hiện tại).</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /assignments.php?course_id=<code>X</code>&past</h3>
            <p>Lấy các bài tập đã qua hạn của khóa học (hạn nộp < hiện tại).</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /assignments.php</h3>
            <p>Tạo bài tập mới:</p>
<pre><code>{
    "course_id": 1,
    "title": "Bài tập lập trình số 1",
    "description": "Viết chương trình Hello World",
    "due_date": "2023-12-15 23:59:00",
    "max_score": 100.00
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /assignments.php?assignment_id=<code>X</code></h3>
            <p>Cập nhật thông tin bài tập.</p>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /assignments.php?assignment_id=<code>X</code></h3>
            <p>Xóa bài tập.</p>
        </div>

        <h2>7. API Nộp bài (Submissions)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /submissions.php?submission_id=<code>X</code></h3>
            <p>Lấy thông tin một bài nộp.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /submissions.php?assignment_id=<code>X</code></h3>
            <p>Lấy tất cả bài nộp của một bài tập.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /submissions.php?assignment_id=<code>X</code>&student_id=<code>Y</code></h3>
            <p>Lấy bài nộp của sinh viên cụ thể cho bài tập cụ thể.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /submissions.php?student_id=<code>X</code></h3>
            <p>Lấy tất cả bài nộp của một sinh viên.</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /submissions.php</h3>
            <p>Nộp bài mới:</p>
<pre><code>{
    "assignment_id": 1,
    "student_id": 2,
    "file_path": "/uploads/submission.pdf",
    "content": "Nội dung bài làm",
    "status": "Submitted"
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /submissions.php?submission_id=<code>X</code></h3>
            <p>Cập nhật bài nộp.</p>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /submissions.php?submission_id=<code>X</code></h3>
            <p>Xóa bài nộp.</p>
        </div>

        <h2>8. API Điểm số (Grades)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /grades.php?grade_id=<code>X</code></h3>
            <p>Lấy thông tin một điểm số.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /grades.php?enrollment_id=<code>X</code></h3>
            <p>Lấy tất cả điểm của một ghi danh.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /grades.php?enrollment_id=<code>X</code>&assignment_id=<code>Y</code></h3>
            <p>Lấy điểm bài tập cụ thể của ghi danh.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /grades.php?assignment_id=<code>X</code></h3>
            <p>Lấy tất cả điểm của một bài tập.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /grades.php?student_id=<code>X</code></h3>
            <p>Lấy tất cả điểm của một sinh viên.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /grades.php?student_id=<code>X</code>&course_id=<code>Y</code></h3>
            <p>Lấy điểm của sinh viên trong khóa học cụ thể.</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /grades.php</h3>
            <p>Tạo điểm số mới:</p>
<pre><code>{
    "enrollment_id": 1,
    "assignment_id": 2,
    "score": 85.50,
    "grade_letter": "B+",
    "comments": "Bài làm tốt",
    "published": false
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /grades.php?grade_id=<code>X</code></h3>
            <p>Cập nhật điểm số.</p>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /grades.php?grade_id=<code>X</code></h3>
            <p>Xóa điểm số.</p>
        </div>

        <h2>9. API Bài kiểm tra trực tuyến (Online Tests)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /onlinetests.php</h3>
            <p>Lấy danh sách tất cả bài kiểm tra.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /onlinetests.php?test_id=<code>X</code></h3>
            <p>Lấy thông tin chi tiết một bài kiểm tra.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /onlinetests.php?course_id=<code>X</code></h3>
            <p>Lấy tất cả bài kiểm tra của khóa học.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /onlinetests.php?course_id=<code>X</code>&upcoming</h3>
            <p>Lấy bài kiểm tra sắp tới (thời gian bắt đầu > hiện tại).</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /onlinetests.php?course_id=<code>X</code>&active</h3>
            <p>Lấy bài kiểm tra đang diễn ra (thời gian hiện tại nằm giữa start và end).</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /onlinetests.php?course_id=<code>X</code>&past</h3>
            <p>Lấy bài kiểm tra đã kết thúc (thời gian kết thúc < hiện tại).</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /onlinetests.php</h3>
            <p>Tạo bài kiểm tra mới:</p>
<pre><code>{
    "course_id": 1,
    "test_name": "Kiểm tra giữa kỳ",
    "description": "Bài kiểm tra 15 câu hỏi trắc nghiệm",
    "start_time": "2023-12-10 09:00:00",
    "end_time": "2023-12-10 10:30:00",
    "duration_minutes": 90,
    "max_score": 100.00
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /onlinetests.php?test_id=<code>X</code></h3>
            <p>Cập nhật thông tin bài kiểm tra.</p>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /onlinetests.php?test_id=<code>X</code></h3>
            <p>Xóa bài kiểm tra.</p>
        </div>

        <h2>10. API Điểm kiểm tra (Test Scores)</h2>
        
        <div class="endpoint method-get">
            <h3>GET /testscores.php?score_id=<code>X</code></h3>
            <p>Lấy thông tin một điểm kiểm tra.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /testscores.php?test_id=<code>X</code></h3>
            <p>Lấy tất cả điểm của một bài kiểm tra.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /testscores.php?test_id=<code>X</code>&student_id=<code>Y</code></h3>
            <p>Lấy điểm kiểm tra của sinh viên cụ thể.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /testscores.php?student_id=<code>X</code></h3>
            <p>Lấy tất cả điểm kiểm tra của sinh viên.</p>
        </div>
        
        <div class="endpoint method-get">
            <h3>GET /testscores.php?student_id=<code>X</code>&course_id=<code>Y</code></h3>
            <p>Lấy điểm kiểm tra của sinh viên trong khóa học cụ thể.</p>
        </div>
        
        <div class="endpoint method-post">
            <h3>POST /testscores.php</h3>
            <p>Tạo điểm kiểm tra mới:</p>
<pre><code>{
    "test_id": 1,
    "student_id": 2,
    "score": 75.50,
    "completion_time": "2023-12-10 10:15:00",
    "status": "Completed"
}</code></pre>
        </div>
        
        <div class="endpoint method-put">
            <h3>PUT /testscores.php?score_id=<code>X</code></h3>
            <p>Cập nhật điểm kiểm tra.</p>
        </div>
        
        <div class="endpoint method-delete">
            <h3>DELETE /testscores.php?score_id=<code>X</code></h3>
            <p>Xóa điểm kiểm tra.</p>
        </div>

        <div class="note">
            <strong>Lưu ý:</strong> Tất cả các API đều trả về dữ liệu dạng JSON. Đối với các phương thức POST và PUT, cần gửi header <code>Content-Type: application/json</code> cùng với dữ liệu JSON trong body của request.
        </div>
    </div>
</body>
</html>