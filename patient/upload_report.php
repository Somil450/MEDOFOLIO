<?php
session_start();
include "../db.php";

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$patient_id = (int) $_SESSION['user_id'];

/* ================= VALIDATION ================= */
if (
    empty($_POST['disease_name']) ||
    !isset($_FILES['report_file'])
) {
    die("Invalid request");
}

$disease_name = mysqli_real_escape_string($conn, $_POST['disease_name']);

/* ================= FILE VALIDATION ================= */
$allowedTypes = ['pdf','jpg','jpeg','png'];
$ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedTypes)) {
    die("Invalid file type. Only PDF, JPG, PNG allowed.");
}

/* ================= UPLOAD DIR ================= */
$uploadDir = __DIR__ . "/../uploads/reports/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* ================= FILE NAME ================= */
$fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "", $_FILES['report_file']['name']);
$targetPath = $uploadDir . $fileName;
$dbPath = "../uploads/reports/" . $fileName;

/* ================= MOVE FILE ================= */
if (!move_uploaded_file($_FILES['report_file']['tmp_name'], $targetPath)) {
    die("File upload failed");
}

/* ================= INSERT DB ================= */
$stmt = mysqli_prepare($conn, "
    INSERT INTO medical_reports
    (patient_id, disease_name, file_name, file_path, uploaded_at)
    VALUES (?, ?, ?, ?, NOW())
");

mysqli_stmt_bind_param(
    $stmt,
    "isss",
    $patient_id,
    $disease_name,
    $fileName,
    $dbPath
);

mysqli_stmt_execute($stmt);

/* ================= REDIRECT ================= */
header("Location: patient_profile.php");
exit;
