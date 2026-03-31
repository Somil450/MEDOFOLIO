<?php
session_start();
include "../db.php";

$error = "";
$success = "";

// Registration success message
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $success = "Registration successful! Please login.";
}

// Redirect if already logged in
if (isset($_SESSION['doctor_id'])) {
    header("Location: ../dashboard/doctor_dashboard.php");
    exit;
}

if (isset($_POST['login'])) {
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email and password required.";
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT doctor_id, name, email, password, specialization, hospital_name, is_active
             FROM doctor_login WHERE email = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {

            if ($row['is_active'] == 1 && password_verify($password, $row['password'])) {

                // 🔐 Security fix
                session_regenerate_id(true);

                $_SESSION['doctor_id'] = $row['doctor_id'];
                $_SESSION['doctor_name'] = $row['name'];
                $_SESSION['doctor_email'] = $row['email'];
                $_SESSION['doctor_specialization'] = $row['specialization'];
                $_SESSION['doctor_hospital'] = $row['hospital_name'];
                $_SESSION['login_time'] = time();

                // Update last login
                $update_stmt = mysqli_prepare($conn,
                    "UPDATE doctor_login SET last_login = CURRENT_TIMESTAMP WHERE doctor_id = ?"
                );

                mysqli_stmt_bind_param($update_stmt, "i", $row['doctor_id']);
                mysqli_stmt_execute($update_stmt);

                // Log login activity
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];

                $log_stmt = mysqli_prepare($conn,
                    "INSERT INTO doctor_activity_log 
                    (doctor_id, activity_type, activity_details, ip_address, user_agent)
                    VALUES (?, 'login', 'Doctor login successful', ?, ?)"
                );

                mysqli_stmt_bind_param($log_stmt, "iss",
                    $row['doctor_id'],
                    $ip_address,
                    $user_agent
                );

                mysqli_stmt_execute($log_stmt);

                header("Location: ../dashboard/doctor_dashboard.php");
                exit;

            } elseif ($row['is_active'] == 0) {
                $error = "Account deactivated.";
            } else {
                $error = "Invalid credentials.";
            }

        } else {
            $error = "Invalid credentials.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - HealthIntel</title>
    <link rel="stylesheet" href="../assets/style-enhanced.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #F8FAFF 0%, #E8F4FD 50%, #F0F8FF 100%);
            position: relative;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            position: relative;
        }

        .login-header {
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .login-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E1E8ED;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(74, 144, 226, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #FEE;
            color: #C33;
            border: 1px solid #FCC;
        }

        .alert-success {
            background: #EFE;
            color: #3C3;
            border: 1px solid #CFC;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color: #4A90E2;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .forgot-link {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: #666;
        }

        .forgot-link a {
            color: #4A90E2;
            text-decoration: none;
        }

        .forgot-link a:hover {
            text-decoration: underline;
        }

            </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Doctor Login</h1>
                <p>Welcome back to HealthIntel</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                
                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="doctor@hospital.com">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter your password">
                    </div>

                    <button type="submit" name="login" class="btn-login">Login</button>
                </form>

                <div class="register-link">
                    Don't have an account? <a href="doctor_register.php">Register here</a> | 
                    <a href="../index.php">Back to Home</a>
                </div>

                <div class="forgot-link">
                    <a href="forgot_password.php">Forgot your password?</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form submission validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please enter both email and password.');
            }
        });
    </script>
</body>
</html>
