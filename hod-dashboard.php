<?php
session_start();

// Check if HOD is logged in
if (!isset($_SESSION["hod_id"])) {
    header("Location: hod-login.php");
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

// Process application approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $remarks = trim($_POST['remarks']);
    
    if ($action == 'approve') {
        $status = 'approved';
    } else {
        $status = 'rejected';
    }
    
    $update_query = "UPDATE student_requests SET hod_status = ?, hod_remarks = ?, hod_updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $status, $remarks, $request_id);
    
    if ($stmt->execute()) {
        $success = "Application " . $status . " successfully!";
    } else {
        $error = "Error updating application: " . $stmt->error;
    }
    $stmt->close();
}

// Get pending applications for this HOD's branch (approved by class teachers)
$branch = $_SESSION["hod_branch"];

$query = "SELECT sr.*, s.name as student_name, s.roll_no, s.enrollment_no, s.year as student_year,
                 ct.name as class_teacher_name
          FROM student_requests sr 
          JOIN students s ON sr.student_id = s.id 
          LEFT JOIN class_teachers ct ON sr.branch = ct.branch AND sr.year = ct.year
          WHERE sr.branch = ? AND sr.hod_status = 'pending' AND sr.class_teacher_status = 'approved'
          ORDER BY sr.submitted_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $branch);
$stmt->execute();
$applications = $stmt->get_result();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_pending,
    SUM(hod_status = 'approved') as total_approved,
    SUM(hod_status = 'rejected') as total_rejected,
    SUM(class_teacher_status = 'approved' AND hod_status = 'pending') as waiting_hod_approval
    FROM student_requests 
    WHERE branch = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("s", $branch);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

$stats_stmt->close();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard - ESMS</title>
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">HOD Dashboard</h1>
                    <p class="text-gray-600">
                        <?php echo $_SESSION["hod_name"]; ?> | 
                        Head of Department - <?php echo $_SESSION["hod_branch"]; ?>
                    </p>
                </div>
                <div class="flex space-x-4">
                    <a href="hod-login.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Switch Department
                    </a>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Approval</dt>
                    <dd class="mt-1 text-3xl font-semibold text-yellow-600"><?php echo $stats['total_pending']; ?></dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                    <dd class="mt-1 text-3xl font-semibold text-green-600"><?php echo $stats['total_approved']; ?></dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Rejected</dt>
                    <dd class="mt-1 text-3xl font-semibold text-red-600"><?php echo $stats['total_rejected']; ?></dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Waiting CT Approval</dt>
                    <dd class="mt-1 text-3xl font-semibold text-blue-600"><?php echo $stats['waiting_hod_approval']; ?></dd>
                </div>
            </div>
        </div>

        <!-- Applications -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Pending Applications - <?php echo $branch; ?> Department
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Applications approved by class teachers waiting for your final approval.
                </p>
            </div>

            <div class="border-t border-gray-200">
                <?php if ($applications->num_rows > 0): ?>
                    <div class="divide-y divide-gray-200">
                        <?php while($app = $applications->fetch_assoc()): 
                            $request_data = json_decode($app['request_data'], true);
                        ?>
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900">
                                        <?php echo $app['student_name']; ?> (<?php echo $app['roll_no']; ?>)
                                    </h4>
                                    <p class="text-sm text-gray-500">
                                        Year <?php echo $app['student_year']; ?> • 
                                        <?php echo strtoupper($app['request_type']); ?> • 
                                        Submitted: <?php echo date('M j, Y g:i A', strtotime($app['submitted_at'])); ?>
                                    </p>
                                    <?php if ($app['class_teacher_remarks']): ?>
                                        <p class="text-sm text-blue-600 mt-1">
                                            <strong>Class Teacher Remarks:</strong> <?php echo $app['class_teacher_remarks']; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <?php echo strtoupper($app['request_type']); ?>
                                    </span>
                                    <div class="mt-1 text-sm text-green-600">
                                        ✓ Approved by Class Teacher
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <?php if ($app['request_type'] == 'leave'): ?>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div><strong>From:</strong> <?php echo $request_data['from_date']; ?></div>
                                        <div><strong>To:</strong> <?php echo $request_data['to_date']; ?></div>
                                        <div><strong>Days:</strong> <?php echo $request_data['days']; ?></div>
                                        <div><strong>Reason:</strong> <?php echo $request_data['reason']; ?></div>
                                        <div class="col-span-2"><strong>Description:</strong> <?php echo $request_data['description']; ?></div>
                                    </div>
                                <?php elseif ($app['request_type'] == 'getpass'): ?>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div><strong>Date:</strong> <?php echo $request_data['pass_date']; ?></div>
                                        <div><strong>Out Time:</strong> <?php echo $request_data['out_time']; ?></div>
                                        <div><strong>Return Time:</strong> <?php echo $request_data['return_time']; ?></div>
                                        <div><strong>Purpose:</strong> <?php echo $request_data['purpose']; ?></div>
                                        <div class="col-span-2"><strong>Destination:</strong> <?php echo $request_data['destination']; ?></div>
                                    </div>
                                <?php else: ?>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div><strong>Purpose:</strong> <?php echo $request_data['purpose']; ?></div>
                                        <div><strong>Required For:</strong> <?php echo $request_data['required_for']; ?></div>
                                        <div><strong>Copies:</strong> <?php echo $request_data['copies']; ?></div>
                                        <div><strong>Urgency:</strong> <?php echo $request_data['urgency']; ?></div>
                                        <?php if (!empty($request_data['additional_info'])): ?>
                                        <div class="col-span-2"><strong>Additional Info:</strong> <?php echo $request_data['additional_info']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form method="POST" action="">
                                <input type="hidden" name="request_id" value="<?php echo $app['id']; ?>">
                                <div class="mb-4">
                                    <label for="remarks_<?php echo $app['id']; ?>" class="block text-sm font-medium text-gray-700 mb-2">
                                        Your Remarks (Optional)
                                    </label>
                                    <textarea name="remarks" id="remarks_<?php echo $app['id']; ?>" 
                                              rows="3"
                                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border border-gray-300 rounded-md"
                                              placeholder="Add any remarks or instructions..."></textarea>
                                </div>
                                <div class="flex space-x-3">
                                    <button type="submit" name="action" value="approve" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        ✓ Final Approve
                                    </button>
                                    <button type="submit" name="action" value="reject" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        ✗ Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="px-4 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No pending applications</h3>
                        <p class="mt-1 text-sm text-gray-500">All applications have been reviewed or are waiting for class teacher approval.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Department Summary</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Overview of <?php echo $branch; ?> department activities.</p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-3">Application Flow</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Student Submits</span>
                                <span class="text-green-600">→</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Class Teacher Reviews</span>
                                <span class="text-green-600">→</span>
                            </div>
                            <div class="flex justify-between">
                                <span>HOD Final Approval</span>
                                <span class="text-green-600">→</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Application Completed</span>
                                <span class="text-green-600">✓</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-3">Quick Actions</h4>
                        <div class="space-y-2">
                            <a href="hod-login.php" class="block w-full text-center bg-blue-50 text-blue-700 px-4 py-2 rounded hover:bg-blue-100">
                                Switch Department
                            </a>
                            <a href="index.html" class="block w-full text-center bg-gray-50 text-gray-700 px-4 py-2 rounded hover:bg-gray-100">
                                Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>