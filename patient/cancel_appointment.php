<?php
session_start();
include "../db.php";

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$patient_id = (int) $_SESSION['user_id'];

/* ================= VALIDATION ================= */
if (empty($_POST['appointment_id'])) {
    die("Invalid request");
}

$appointment_id = (int) $_POST['appointment_id'];

/* ================= DELETE (OWNERSHIP SAFE) ================= */
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM appointments
     WHERE appointment_id = ? AND patient_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $appointment_id,
    $patient_id
);

mysqli_stmt_execute($stmt);

/* ================= REDIRECT ================= */
header("Location: my_appointments.php?cancelled=1");
exit;
