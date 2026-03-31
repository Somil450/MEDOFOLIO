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
    empty($_POST['disease']) ||
    empty($_POST['specialty']) ||
    empty($_POST['hospital_type']) ||
    empty($_POST['date']) ||
    empty($_POST['time'])
) {
    die("Invalid appointment data");
}

/* ================= SANITIZE ================= */
$disease      = mysqli_real_escape_string($conn, $_POST['disease']);
$specialty    = mysqli_real_escape_string($conn, $_POST['specialty']);
$hospitalType = mysqli_real_escape_string($conn, $_POST['hospital_type']);
$date         = $_POST['date']; // YYYY-MM-DD
$time         = $_POST['time']; // HH:MM
$status       = "Booked";

/* ================= INSERT ================= */
$stmt = mysqli_prepare($conn, "
    INSERT INTO appointments
    (patient_id, disease, specialty, hospital_type, appointment_date, appointment_time, status)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

mysqli_stmt_bind_param(
    $stmt,
    "issssss",
    $patient_id,
    $disease,
    $specialty,
    $hospitalType,
    $date,
    $time,
    $status
);

if (mysqli_stmt_execute($stmt)) {
    header("Location: my_appointments.php?booked=1");
    exit;
} else {
    die("Appointment booking failed");
}
