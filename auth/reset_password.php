<?php
session_start();
include "../db.php";

if (!isset($_GET['token'])) {
    die("Invalid reset link");
}

$token = $_GET['token'];

// Check token (NO TIME CHECK)
$stmt = mysqli_prepare($conn,
    "SELECT user_id FROM users WHERE reset_token = ?"
);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) !== 1) {
    die("Reset link expired or invalid");
}

$user = mysqli_fetch_assoc($result);
$user_id = $user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn,
        "UPDATE users
         SET password = ?, reset_token = NULL
         WHERE user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "si", $password, $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: login.php?reset=success");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
<link rel="stylesheet" href="../assets/style.css">

<style>
.password-box {
    position: relative;
}
.password-box input {
    width: 100%;
    padding-right: 45px;
}
.password-box span {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 18px;
    color: #555;
}
</style>
</head>
<body>

<div class="card" style="max-width:400px;margin:100px auto;">
    <h2>Reset Password</h2>

     <form method="POST">
        <div class="password-box">
            <input type="password"
                   id="password"
                   name="password"
                   placeholder="New Password"
                   required>
            <span onclick="togglePassword()">👁</span>
        </div>

        <button class="btn">Reset Password</button>
    </form>
</div>
<script>
function togglePassword() {
    const pass = document.getElementById("password");
    pass.type = pass.type === "password" ? "text" : "password";
}
</script>
</body>
</html>
