<?php
session_start();
include "../db.php";

if (isset($_POST['register'])) {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert user
    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $password);

    if (mysqli_stmt_execute($stmt)) {

        $user_id = mysqli_insert_id($conn);

        // Auto create patient
        mysqli_query($conn,"
            INSERT INTO patient (patient_id, name, region)
            VALUES ($user_id, '$name', 'India')
        ");

        header("Location: login.php");
        exit;
    } else {
        $error = "Email already exists";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - MedoFolio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/style-enhanced.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #FFFFFF 0%, #F0F8FF 50%, #E8F4FF 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            top: 100px;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 102, 204, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 102, 204, 0.1);
            max-width: 450px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .register-container::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(0, 102, 204, 0.1);
            border-radius: 50%;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .register-header h1 {
            font-size: clamp(28px, 4vw, 36px);
            font-weight: 800;
            background: linear-gradient(45deg, #0052CC, #0066FF, #0052CC);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .register-header p {
            color: var(--neutral-gray);
            font-size: 16px;
            opacity: 0.8;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--neutral-dark);
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid rgba(0, 102, 204, 0.2);
            border-radius: 12px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.8);
            color: var(--neutral-dark);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-medical);
            box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.2);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px);
        }
        
        .register-btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(45deg, var(--primary-medical), var(--medical-blue));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 24px rgba(0, 102, 204, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .register-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(0, 102, 204, 0.4);
        }
        
        .register-btn:hover::before {
            left: 100%;
        }
        
        .error-message {
            background: rgba(209, 52, 56, 0.1);
            color: var(--danger-red);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(209, 52, 56, 0.2);
            font-size: 14px;
            text-align: center;
            animation: shake 0.5s ease-in-out;
        }
        
        .login-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid rgba(0, 102, 204, 0.1);
        }
        
        .login-link a {
            color: var(--primary-medical);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .login-link a:hover {
            color: var(--medical-blue);
            text-decoration: underline;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
                top: 80px;
            }
            
            .register-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .register-header h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Join MedoFolio</h1>
            <p>Create your health management account</p>
        </div>
        
        <form method="POST">
            <?php if(isset($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email address" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a strong password" required>
            </div>
            
            <button type="submit" name="register" class="register-btn">
                Create Account
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>
</body>
</html>
