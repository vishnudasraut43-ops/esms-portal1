<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "esms_portal";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Fixed password for all HODs
    $fixed_password = "YCIP@123";
    
    if ($password !== $fixed_password) {
        $error = "Invalid password! Please use YCIP@123";
    } else {
        $query = "SELECT * FROM hods WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $hod = $result->fetch_assoc();
            $_SESSION["hod_id"] = $hod["id"];
            $_SESSION["hod_name"] = $hod["name"];
            $_SESSION["hod_branch"] = $hod["branch"];
            header("Location: hod-dashboard.php");
            exit();
        } else {
            $error = "HOD not found!";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Login - ESMS</title>
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .login-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        .department-option {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .department-option:hover {
            border-color: #667eea;
            background-color: #f7fafc;
            transform: translateY(-2px);
        }
        .department-option.selected {
            border-color: #667eea;
            background-color: #edf2f7;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .branch-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .branch-cm { color: #1e40af; }
        .branch-ej { color: #be185d; }
        .branch-me { color: #166534; }
        .branch-ce { color: #92400e; }
        .branch-ai { color: #3730a3; }
        .branch-ee { color: #6b21a8; }
        .branch-it { color: #c2410c; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="text-center mb-2">
                <a href="index.html" class="text-blue-500 hover:text-blue-700 text-sm font-medium">← Back to Home</a>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-800 mb-2 text-center">HOD Login</h1>
            <p class="text-gray-600 text-center mb-8">Select your department</p>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="login" value="1">
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-3">Select Your Department</label>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Computer Engineering -->
                        <div class="department-option" onclick="selectOption('hodcm')">
                            <div class="branch-icon branch-cm">💻</div>
                            <div class="font-semibold text-gray-800">Computer</div>
                            <div class="text-sm text-gray-500">hodcm</div>
                        </div>
                        
                        <!-- Electronics Engineering -->
                        <div class="department-option" onclick="selectOption('hodej')">
                            <div class="branch-icon branch-ej">🔌</div>
                            <div class="font-semibold text-gray-800">Electronics</div>
                            <div class="text-sm text-gray-500">hodej</div>
                        </div>
                        
                        <!-- Mechanical Engineering -->
                        <div class="department-option" onclick="selectOption('hodme')">
                            <div class="branch-icon branch-me">⚙️</div>
                            <div class="font-semibold text-gray-800">Mechanical</div>
                            <div class="text-sm text-gray-500">hodme</div>
                        </div>
                        
                        <!-- Civil Engineering -->
                        <div class="department-option" onclick="selectOption('hodce')">
                            <div class="branch-icon branch-ce">🏗️</div>
                            <div class="font-semibold text-gray-800">Civil</div>
                            <div class="text-sm text-gray-500">hodce</div>
                        </div>
                        
                        <!-- Artificial Intelligence -->
                        <div class="department-option" onclick="selectOption('hodai')">
                            <div class="branch-icon branch-ai">🤖</div>
                            <div class="font-semibold text-gray-800">AI</div>
                            <div class="text-sm text-gray-500">hodai</div>
                        </div>
                        
                        <!-- Electrical Engineering -->
                        <div class="department-option" onclick="selectOption('hodee')">
                            <div class="branch-icon branch-ee">⚡</div>
                            <div class="font-semibold text-gray-800">Electrical</div>
                            <div class="text-sm text-gray-500">hodee</div>
                        </div>
                        
                        <!-- Information Technology -->
                        <div class="department-option" onclick="selectOption('hodit')">
                            <div class="branch-icon branch-it">🌐</div>
                            <div class="font-semibold text-gray-800">IT</div>
                            <div class="text-sm text-gray-500">hodit</div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="username" name="username" required>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                    <input type="password" id="password" name="password" value="YCIP@123" required 
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-center font-mono bg-gray-50">
                    <p class="text-sm text-gray-500 mt-1 text-center">Default password: YCIP@123</p>
                </div>
                
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-green-600 to-blue-600 text-white py-3 px-4 rounded-lg hover:from-green-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 font-semibold text-lg">
                    Login as HOD
                </button>
            </form>
            
            <div class="mt-6 p-4 bg-green-50 rounded-lg border border-green-200">
                <h3 class="font-semibold text-green-800 mb-2">Login Instructions:</h3>
                <ul class="text-sm text-green-700 space-y-1">
                    <li>• Click on your department</li>
                    <li>• Password is fixed: <strong>YCIP@123</strong></li>
                    <li>• You will see applications approved by class teachers from your department</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        let selectedOption = null;
        
        function selectOption(username) {
            // Remove selected class from all options
            document.querySelectorAll('.department-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Set the username value
            document.getElementById('username').value = username;
            selectedOption = username;
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!selectedOption) {
                e.preventDefault();
                alert('Please select your department');
                return false;
            }
        });
    </script>
</body>
</html>