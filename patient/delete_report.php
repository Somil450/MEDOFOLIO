<?php
session_start();
include "../db.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$patient_id = (int) $_SESSION['user_id'];

/* ================= VALIDATION ================= */
if (empty($_POST['report_id'])) {
    die("Invalid request");
}

$report_id = (int) $_POST['report_id'];

/* ================= VERIFY OWNERSHIP ================= */
$stmt = mysqli_prepare($conn, "
    SELECT file_path
    FROM medical_reports
    WHERE report_id = ? AND patient_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $report_id, $patient_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    $filePath = __DIR__ . "/../" . ltrim($row['file_path'], "/");

    /* DELETE FILE */
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    /* DELETE DB RECORD */
    $del = mysqli_prepare($conn, "
        DELETE FROM medical_reports
        WHERE report_id = ? AND patient_id = ?
    ");
    mysqli_stmt_bind_param($del, "ii", $report_id, $patient_id);
    mysqli_stmt_execute($del);
}

/* ================= REDIRECT ================= */
header("Location: patient_profile.php");
exit;
