<?php
session_start();
include "../db.php";

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = mysqli_prepare(
        $conn,
        "SELECT user_id, password FROM users WHERE email=?"
    );
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        if (password_verify($password, $row['password'])) {

            $_SESSION['user'] = $email;
            $_SESSION['user_id'] = $row['user_id'];

            header("Location: ../dashboard/dashboard.php");
            exit;
        }
    }

    $error = "Invalid email or password";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MedoFolio - Medical Portal Login</title>
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
    overflow: hidden;
}

.login-container::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%230066CC' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    50% { transform: translate(-30px, -30px) rotate(180deg); }
}

.login-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 48px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 60px rgba(0, 102, 204, 0.15);
    border: 1px solid rgba(0, 102, 204, 0.1);
    position: relative;
    z-index: 1;
    animation: slideUp 0.6s ease;
}

.login-header {
    text-align: center;
    margin-bottom: 40px;
}

.medical-logo {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-medical), var(--medical-blue));
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 36px;
    color: white;
    box-shadow: 0 8px 24px rgba(0, 102, 204, 0.2);
}

.login-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary-medical);
    margin-bottom: 8px;
}

.login-header p {
    color: var(--neutral-gray);
    font-size: 16px;
    font-weight: 400;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: var(--neutral-gray);
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 16px 20px;
    border: 2px solid #E1DFDD;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: var(--white);
}

.form-group input:focus {
    border-color: var(--primary-medical);
    box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
    outline: none;
}

.password-toggle {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 18px;
    color: var(--neutral-gray);
    transition: color 0.3s ease;
    background: none;
    border: none;
}

.password-toggle:hover {
    color: var(--primary-medical);
}

.login-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, var(--primary-medical), var(--medical-blue));
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 32px;
    position: relative;
    overflow: hidden;
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(0, 102, 204, 0.3);
}

.login-btn:active {
    transform: translateY(0);
}

.divider {
    text-align: center;
    margin: 32px 0;
    position: relative;
}

.divider::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #E1DFDD;
}

.divider span {
    background: white;
    padding: 0 16px;
    color: var(--neutral-gray);
    font-size: 14px;
    position: relative;
}

.login-links {
    text-align: center;
    margin-top: 24px;
}

.login-links a {
    color: var(--primary-medical);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.login-links a:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

.login-links p {
    color: var(--neutral-gray);
    margin-bottom: 8px;
    font-size: 14px;
}

.alert {
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 500;
}

.alert-success {
    background: rgba(16, 124, 16, 0.1);
    color: var(--health-green);
    border: 1px solid rgba(16, 124, 16, 0.2);
}

.alert-error {
    background: rgba(209, 52, 56, 0.1);
    color: var(--danger-red);
    border: 1px solid rgba(209, 52, 56, 0.2);
}

.input-icon {
    position: relative;
}

.input-icon::before {
    content: "";
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    opacity: 0.5;
}

.input-icon.email::before {
    content: "📧";
}

.input-icon.password::before {
    content: "🔒";
}

.input-icon input {
    padding-left: 50px;
}

@media (max-width: 480px) {
    .login-card {
        padding: 32px 24px;
        margin: 16px;
    }
    
    .login-header h1 {
        font-size: 24px;
    }
    
    .medical-logo {
        width: 64px;
        height: 64px;
        font-size: 28px;
    }
}
</style>
</head>

<body>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="medical-logo">🏥</div>
            <h1>MedoFolio</h1>
            <p>Medical Portal Login</p>
        </div>

        <?php if (isset($_GET['reset'])): ?>
            <div class="alert alert-success">
                ✓ Password reset successful. Please login with your new password.
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon email">
                    <input type="email"
                           id="email"
                           name="email"
                           placeholder="Enter your email address"
                           required
                           autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon password">
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Enter your password"
                           required
                           autocomplete="current-password">
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <span id="eyeIcon">👁️</span>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="login-btn">
                Sign In to Medical Portal
            </button>
        </form>

        <div class="divider">
            <span>or</span>
        </div>

        <div class="login-links">
            <p>
                <a href="forgot_password.php">Forgot your password?</a>
            </p>
            <p>
                New to MedoFolio? <a href="register.php">Create an account</a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.textContent = '👁️‍🗨️';
    } else {
        passwordInput.type = 'password';
        eyeIcon.textContent = '👁️';
    }
}

// Add subtle animations on load
document.addEventListener('DOMContentLoaded', function() {
    const card = document.querySelector('.login-card');
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        card.style.transition = 'all 0.6s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, 100);
});
</script>

</body>
</html>
