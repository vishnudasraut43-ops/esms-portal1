<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION["student_id"])) {
    header("Location: student-login.php");
    exit();
}

// Display success/error messages
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $type = htmlspecialchars($_GET['type']);
    
    echo "<div class='message {$type}' style='margin: 20px auto; max-width: 1000px;'>
            {$message}
            <button onclick='this.parentElement.style.display=\"none\"' style='float: right; background: none; border: none; font-size: 20px; cursor: pointer;'>&times;</button>
          </div>";
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

// Get student details
$student_id = $_SESSION["student_id"];
$query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Student not found, logout
    session_destroy();
    header("Location: student-login.php?message=" . urlencode("❌ Student session expired. Please login again.") . "&type=error");
    exit();
}

$student = $result->fetch_assoc();
$stmt->close();

// Get student's applications
$applications_query = "SELECT * FROM student_requests WHERE student_id = ? ORDER BY submitted_at DESC";
$apps_stmt = $conn->prepare($applications_query);
$apps_stmt->bind_param("i", $student_id);
$apps_stmt->execute();
$applications = $apps_stmt->get_result();
$apps_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - ESMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .dashboard { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 15px 30px rgba(0,0,0,0.2); max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #eee; flex-wrap: wrap; }
        h1 { color: #333; margin-bottom: 10px; }
        .student-welcome { color: #666; font-size: 18px; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #764ba2; }
        .options-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .option-card { background: white; border-radius: 12px; padding: 25px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 2px solid transparent; transition: all 0.3s ease; cursor: pointer; }
        .option-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); border-color: #764ba2; }
        .option-icon { font-size: 48px; margin-bottom: 15px; color: #764ba2; }
        .option-title { font-size: 22px; font-weight: 600; color: #333; margin-bottom: 10px; }
        .option-description { color: #666; font-size: 14px; line-height: 1.5; }
        .student-info { background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 30px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .info-item { margin-bottom: 15px; }
        .info-label { font-weight: 600; color: #555; display: block; margin-bottom: 5px; font-size: 14px; }
        .info-value { color: #333; font-size: 16px; padding: 8px 12px; background: white; border-radius: 6px; border-left: 4px solid #764ba2; }
        .logout { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.3s; text-decoration: none; display: inline-block; }
        .logout:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .actions { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .modal-title { font-size: 24px; color: #333; font-weight: 600; }
        .close-modal { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
        .close-modal:hover { color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; }
        .form-textarea { height: 100px; resize: vertical; }
        .submit-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 25px; border-radius: 6px; font-size: 16px; cursor: pointer; width: 100%; font-weight: 600; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Applications Section */
        .applications-section { margin-top: 40px; }
        .applications-grid { display: grid; grid-template-columns: 1fr; gap: 15px; margin-top: 20px; }
        .application-card { background: white; border-radius: 8px; padding: 20px; border-left: 4px solid #764ba2; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .application-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .application-type { font-weight: 600; color: #333; }
        .application-date { color: #666; font-size: 14px; }
        .application-status { display: flex; gap: 20px; margin-top: 10px; }
        .status-item { display: flex; align-items: center; gap: 5px; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .dashboard { padding: 20px; }
            .header { flex-direction: column; text-align: center; gap: 15px; }
            .options-grid { grid-template-columns: 1fr; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <div>
                <h1>Student Dashboard</h1>
                <div class="student-welcome">
                    Welcome back, <strong><?php echo $student['name']; ?></strong>! 
                    (Roll No: <?php echo $student['roll_no']; ?>)
                </div>
            </div>
            <a href="logout.php" class="logout">Logout</a>
        </div>

        <!-- Three Options Grid -->
        <div class="options-grid">
            <div class="option-card" onclick="openModal('leave')">
                <div class="option-icon">📝</div>
                <div class="option-title">LEAVE APPLICATION</div>
                <div class="option-description">Apply for leave from college. Submit your leave request with reason and duration.</div>
            </div>

            <div class="option-card" onclick="openModal('getpass')">
                <div class="option-icon">🎫</div>
                <div class="option-title">GET PASS</div>
                <div class="option-description">Generate gate pass for going outside campus during college hours.</div>
            </div>

            <div class="option-card" onclick="openModal('bonafide')">
                <div class="option-icon">📄</div>
                <div class="option-title">BONAFIDE CERTIFICATE</div>
                <div class="option-description">Request bonafide certificate for various purposes like scholarships, bank accounts, etc.</div>
            </div>
        </div>

        <!-- Applications Status Section -->
        <div class="applications-section">
            <h2 style="margin-bottom: 20px; color: #333;">Your Applications</h2>
            <?php if ($applications->num_rows > 0): ?>
                <div class="applications-grid">
                    <?php while($app = $applications->fetch_assoc()): 
                        $request_data = json_decode($app['request_data'], true);
                        $status_class_ct = "status-" . $app['class_teacher_status'];
                        $status_class_hod = "status-" . $app['hod_status'];
                    ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="application-type">
                                <?php echo strtoupper($app['request_type']); ?> - 
                                <?php echo isset($request_data['reason']) ? $request_data['reason'] : 
                                      (isset($request_data['purpose']) ? $request_data['purpose'] : 'Application'); ?>
                            </div>
                            <div class="application-date">
                                <?php echo date('M j, Y g:i A', strtotime($app['submitted_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="application-status">
                            <div class="status-item">
                                <span>Class Teacher:</span>
                                <span class="status-badge <?php echo $status_class_ct; ?>">
                                    <?php echo ucfirst($app['class_teacher_status']); ?>
                                </span>
                            </div>
                            <div class="status-item">
                                <span>HOD:</span>
                                <span class="status-badge <?php echo $status_class_hod; ?>">
                                    <?php echo ucfirst($app['hod_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($app['class_teacher_remarks']): ?>
                        <div style="margin-top: 10px; font-size: 14px; color: #666;">
                            <strong>Class Teacher Remarks:</strong> <?php echo $app['class_teacher_remarks']; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($app['hod_remarks']): ?>
                        <div style="margin-top: 5px; font-size: 14px; color: #666;">
                            <strong>HOD Remarks:</strong> <?php echo $app['hod_remarks']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 20px;">No applications submitted yet.</p>
            <?php endif; ?>
        </div>

        <!-- Student Information -->
        <div class="student-info">
            <h2 style="margin-bottom: 20px; color: #333;">Student Information</h2>
            <div class="info-grid">
                <div class="info-item"><span class="info-label">Full Name</span><div class="info-value"><?php echo $student['name']; ?></div></div>
                <div class="info-item"><span class="info-label">Roll Number</span><div class="info-value"><?php echo $student['roll_no']; ?></div></div>
                <div class="info-item"><span class="info-label">Enrollment Number</span><div class="info-value"><?php echo $student['enrollment_no']; ?></div></div>
                <div class="info-item"><span class="info-label">Branch</span><div class="info-value"><?php echo $student['branch']; ?></div></div>
                <div class="info-item"><span class="info-label">Year</span><div class="info-value"><?php echo $student['year']; ?> Year</div></div>
                <div class="info-item"><span class="info-label">Email</span><div class="info-value"><?php echo $student['email']; ?></div></div>
            </div>
        </div>

        <div class="actions">
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>

    <!-- LEAVE Application Modal -->
    <div id="leaveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Leave Application</h2>
                <button class="close-modal" onclick="closeModal('leave')">×</button>
            </div>
            <form id="leaveForm" method="POST" action="submit-request.php">
                <input type="hidden" name="request_type" value="leave">
                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                <input type="hidden" name="student_name" value="<?php echo $student['name']; ?>">
                <input type="hidden" name="roll_no" value="<?php echo $student['roll_no']; ?>">
                
                <div class="form-group"><label class="form-label">From Date</label><input type="date" class="form-input" name="from_date" required></div>
                <div class="form-group"><label class="form-label">To Date</label><input type="date" class="form-input" name="to_date" required></div>
                <div class="form-group"><label class="form-label">Number of Days</label><input type="number" class="form-input" name="days" min="1" required></div>
                <div class="form-group">
                    <label class="form-label">Reason for Leave</label>
                    <select class="form-select" name="reason" required>
                        <option value="">Select Reason</option><option value="Medical">Medical</option><option value="Personal">Personal</option>
                        <option value="Family Function">Family Function</option><option value="Emergency">Emergency</option><option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Detailed Description</label><textarea class="form-textarea" name="description" placeholder="Please provide detailed reason..." required></textarea></div>
                <button type="submit" class="submit-btn">Submit Leave Application</button>
            </form>
        </div>
    </div>

    <!-- GETPASS Modal -->
    <div id="getpassModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Get Gate Pass</h2>
                <button class="close-modal" onclick="closeModal('getpass')">×</button>
            </div>
            <form id="getpassForm" method="POST" action="submit-request.php">
                <input type="hidden" name="request_type" value="getpass">
                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                <input type="hidden" name="student_name" value="<?php echo $student['name']; ?>">
                <input type="hidden" name="roll_no" value="<?php echo $student['roll_no']; ?>">
                
                <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-input" name="pass_date" required></div>
                <div class="form-group"><label class="form-label">Out Time</label><input type="time" class="form-input" name="out_time" required></div>
                <div class="form-group"><label class="form-label">Expected Return Time</label><input type="time" class="form-input" name="return_time" required></div>
                <div class="form-group">
                    <label class="form-label">Purpose</label>
                    <select class="form-select" name="purpose" required>
                        <option value="">Select Purpose</option><option value="Medical">Medical Appointment</option><option value="Bank Work">Bank Work</option>
                        <option value="Personal Work">Personal Work</option><option value="Stationary">Buy Stationary</option><option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Destination</label><input type="text" class="form-input" name="destination" placeholder="Where are you going?" required></div>
                <button type="submit" class="submit-btn">Generate Gate Pass</button>
            </form>
        </div>
    </div>

    <!-- BONAFIDE Modal -->
    <div id="bonafideModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Bonafide Certificate Request</h2>
                <button class="close-modal" onclick="closeModal('bonafide')">×</button>
            </div>
            <form id="bonafideForm" method="POST" action="submit-request.php">
                <input type="hidden" name="request_type" value="bonafide">
                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                <input type="hidden" name="student_name" value="<?php echo $student['name']; ?>">
                <input type="hidden" name="roll_no" value="<?php echo $student['roll_no']; ?>">
                
                <div class="form-group">
                    <label class="form-label">Purpose of Certificate</label>
                    <select class="form-select" name="purpose" required>
                        <option value="">Select Purpose</option><option value="Scholarship">Scholarship</option><option value="Bank Account">Bank Account</option>
                        <option value="Passport">Passport Application</option><option value="Visa">Visa Application</option><option value="Education Loan">Education Loan</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Required For</label><input type="text" class="form-input" name="required_for" placeholder="e.g., Bank Name, Scholarship Name, etc." required></div>
                <div class="form-group"><label class="form-label">Number of Copies</label><input type="number" class="form-input" name="copies" min="1" max="5" value="1" required></div>
                <div class="form-group"><label class="form-label">Additional Information</label><textarea class="form-textarea" name="additional_info" placeholder="Any additional requirements or information..."></textarea></div>
                <div class="form-group">
                    <label class="form-label">Urgency</label>
                    <select class="form-select" name="urgency" required>
                        <option value="Normal">Normal (3-5 days)</option><option value="Urgent">Urgent (1-2 days)</option><option value="Very Urgent">Very Urgent (Same day)</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Request Bonafide Certificate</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(type) { document.getElementById(type + 'Modal').style.display = 'flex'; }
        function closeModal(type) { document.getElementById(type + 'Modal').style.display = 'none'; }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Set min date to today for date inputs
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.min = today;
        });
    </script>
</body>
</html>