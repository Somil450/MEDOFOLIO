<?php
session_start();
include "../db.php";

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $token = bin2hex(random_bytes(32));

    $stmt = mysqli_prepare($conn,
        "UPDATE users
         SET reset_token = ?
         WHERE email = ?"
    );
    mysqli_stmt_bind_param($stmt, "ss", $token, $email);
    mysqli_stmt_execute($stmt);

    // Always show success (security)
    $success = true;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Forgot Password</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="card" style="max-width:420px;margin:120px auto;">
    <h2>Forgot Password</h2>

    <?php if ($success): ?>
        <p style="color:green;">
            If this email exists, a reset link has been generated.
        </p>
        <p style="font-size:13px;color:#666;">
            (Demo: copy token from database to reset)
        </p>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter email" required>
        <button class="btn">Send Reset Link</button>
    </form>

    <p style="margin-top:15px;">
        <a href="login.php">Back to Login</a>
    </p>
</div>

</body>
</html>
