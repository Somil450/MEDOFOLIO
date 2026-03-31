<?php
session_start();
include "../db.php";

$error = "";
$success = "";

// Redirect if already logged in
if (isset($_SESSION['doctor_id'])) {
    header("Location: ../dashboard/doctor_dashboard.php");
    exit;
}

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $specialization = $_POST['specialization'];
    $hospital_name = trim($_POST['hospital_name']);
    $license_number = trim($_POST['license_number']);
    $phone = trim($_POST['phone']);
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($specialization)) {
        $error = "Name, email, password, and specialization are required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $check_stmt = mysqli_prepare($conn, "SELECT doctor_id FROM doctor_login WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Email already registered. Please use a different email.";
        } else {
            // Insert new doctor
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO doctor_login (name, email, password, specialization, hospital_name, license_number, phone) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "sssssss", $name, $email, $hashed_password, $specialization, $hospital_name, $license_number, $phone);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Registration successful! You can now login.";
                // Log registration activity
                $doctor_id = mysqli_insert_id($conn);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                
                // Fixed: Include all 7 parameters for 7 columns (excluding auto-increment id)
                $activity_details = "Doctor registered";
                $log_stmt = mysqli_prepare($conn,
                    "INSERT INTO doctor_activity_log 
                    (doctor_id, patient_id, activity_type, activity_details, ip_address, user_agent, created_at)
                    VALUES (?, NULL, 'register', ?, ?, ?, NOW())"
                );
                mysqli_stmt_bind_param($log_stmt, "isss",
                    $doctor_id,
                    $activity_details,
                    $ip_address,
                    $user_agent
                );
                mysqli_stmt_execute($log_stmt);
                
                // Redirect to login page after successful registration
                header("Location: doctor_login.php?registration=success");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration - HealthIntel</title>
    <link rel="stylesheet" href="../assets/style-enhanced.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #F8FAFF 0%, #E8F4FD 50%, #F0F8FF 100%);
            position: relative;
        }

        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            position: relative;
        }

        .register-header {
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .register-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .register-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .register-body {
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E1E8ED;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .btn-register {
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

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(74, 144, 226, 0.3);
        }

        .btn-register:active {
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

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #4A90E2;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        .specialization-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>Doctor Registration</h1>
                <p>Join HealthIntel - Open Medical Professional Network</p>
            </div>
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" id="registerForm">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               placeholder="Dr. John Smith">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="doctor@hospital.com">
                    </div>

                    <div class="form-group">
                        <label for="specialization">Specialization *</label>
                        <select id="specialization" name="specialization" required class="specialization-select">
                            <option value="">Select Specialization</option>
                            <option value="Cardiologist" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'Cardiologist') ? 'selected' : ''; ?>>Cardiologist</option>
                            <option value="Neurologist" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'Neurologist') ? 'selected' : ''; ?>>Neurologist</option>
                            <option value="Pediatrician" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'Pediatrician') ? 'selected' : ''; ?>>Pediatrician</option>
                            <option value="General Physician" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'General Physician') ? 'selected' : ''; ?>>General Physician</option>
                            <option value="Orthopedic" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'Orthopedic') ? 'selected' : ''; ?>>Orthopedic</option>
                            <option value="Dermatologist" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'Dermatologist') ? 'selected' : ''; ?>>Dermatologist</option>
                            <option value="Psychiatrist" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'Psychiatrist') ? 'selected' : ''; ?>>Psychiatrist</option>
                            <option value="Surgeon" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'Surgeon') ? 'selected' : ''; ?>>Surgeon</option>
                            <option value="Gynecologist" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'Gynecologist') ? 'selected' : ''; ?>>Gynecologist</option>
                            <option value="Other" <?php echo (isset($_POST['specialization']) && $_POST['specialization'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="hospital_name">Hospital/Clinic Name</label>
                        <input type="text" id="hospital_name" name="hospital_name" 
                               value="<?php echo isset($_POST['hospital_name']) ? htmlspecialchars($_POST['hospital_name']) : ''; ?>"
                               placeholder="City General Hospital (Optional)">
                    </div>

                    <div class="form-group">
                        <label for="license_number">Medical License Number</label>
                        <input type="text" id="license_number" name="license_number" 
                               value="<?php echo isset($_POST['license_number']) ? htmlspecialchars($_POST['license_number']) : ''; ?>"
                               placeholder="MD-123456 (Optional)">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                               placeholder="+1 (555) 123-4567 (Optional)">
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Min 6 characters" minlength="6">
                        <div class="password-strength">Password must be at least 6 characters long</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Re-enter password">
                    </div>

                    <button type="submit" name="register" class="btn-register">Register as Doctor</button>
                </form>

                <div class="login-link">
                    Already have an account? <a href="doctor_login.php">Login here</a> | 
                    <a href="../index.php">Back to Home</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form submission validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
