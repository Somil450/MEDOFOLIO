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
if (
    empty($_POST['appointment_id']) ||
    empty($_POST['new_date']) ||
    empty($_POST['new_time'])
) {
    die("Invalid reschedule data");
}

$appointment_id = (int) $_POST['appointment_id'];
$new_date = $_POST['new_date']; // YYYY-MM-DD
$new_time = $_POST['new_time']; // HH:MM

/* ================= DATE CHECK ================= */
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date)) {
    die("Invalid date format");
}

/* ================= OWNERSHIP CHECK ================= */
$stmt = mysqli_prepare($conn, "
    UPDATE appointments
    SET appointment_date = ?, appointment_time = ?, status = 'Rescheduled'
    WHERE appointment_id = ? AND patient_id = ?
");

mysqli_stmt_bind_param(
    $stmt,
    "ssii",
    $new_date,
    $new_time,
    $appointment_id,
    $patient_id
);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    header("Location: my_appointments.php?rescheduled=1");
    exit;
} else {
    die("Reschedule failed or unauthorized");
}
