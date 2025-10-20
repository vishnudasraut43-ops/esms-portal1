<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION["student_id"])) {
    header("Location: student-login.php");
    exit();
}

// Database connection
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "esms_portal";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_type = $_POST['request_type'];
    $student_id = $_POST['student_id'];
    $student_name = $_POST['student_name'];
    $roll_no = $_POST['roll_no'];
    
    // Get student details for branch and year
    $student_query = "SELECT branch, year FROM students WHERE id = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    
    if ($student_result->num_rows == 0) {
        header("Location: student-dashboard.php?message=" . urlencode("❌ Student not found!") . "&type=error");
        exit();
    }
    
    $student_data = $student_result->fetch_assoc();
    $branch = $student_data['branch'];
    $year = $student_data['year'];
    $stmt->close();
    
    // Prepare request data based on type
    $request_data = [];
    
    if ($request_type == 'leave') {
        $request_data = [
            'from_date' => $_POST['from_date'],
            'to_date' => $_POST['to_date'],
            'days' => $_POST['days'],
            'reason' => $_POST['reason'],
            'description' => $_POST['description']
        ];
    } elseif ($request_type == 'getpass') {
        $request_data = [
            'pass_date' => $_POST['pass_date'],
            'out_time' => $_POST['out_time'],
            'return_time' => $_POST['return_time'],
            'purpose' => $_POST['purpose'],
            'destination' => $_POST['destination']
        ];
    } elseif ($request_type == 'bonafide') {
        $request_data = [
            'purpose' => $_POST['purpose'],
            'required_for' => $_POST['required_for'],
            'copies' => $_POST['copies'],
            'additional_info' => $_POST['additional_info'],
            'urgency' => $_POST['urgency']
        ];
    }
    
    // Insert into database
    $insert_query = "INSERT INTO student_requests (student_id, student_name, roll_no, branch, year, request_type, request_data, submitted_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($insert_query);
    $json_data = json_encode($request_data);
    $stmt->bind_param("isssiss", $student_id, $student_name, $roll_no, $branch, $year, $request_type, $json_data);
    
    if ($stmt->execute()) {
        header("Location: student-dashboard.php?message=" . urlencode("✅ Application submitted successfully!") . "&type=success");
    } else {
        header("Location: student-dashboard.php?message=" . urlencode("❌ Error submitting application: " . $stmt->error) . "&type=error");
    }
    
    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: student-dashboard.php");
    exit();
}
?>