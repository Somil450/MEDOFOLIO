<?php
session_start();

/* If logged in → dashboard */
if (isset($_SESSION['user'])) {
    header("Location: dashboard/dashboard.php");
    exit;
}

/* Else → login */
header("Location: auth/login.php");
exit;
