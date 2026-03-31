<?php
session_start();

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

include "../db.php";

/* ================= GET FILE PATH ================= */
if (!isset($_GET['file'])) {
    die("No file specified");
}

$filePath = $_GET['file'];
$patient_id = (int) $_SESSION['user_id'];

/* ================= SECURITY CHECK ================= */
// Verify the file belongs to the current patient
$reportQuery = mysqli_query($conn, "
    SELECT file_path, file_name 
    FROM medical_reports 
    WHERE patient_id = $patient_id AND file_path = '" . mysqli_real_escape_string($conn, $filePath) . "'
");

if (!$reportQuery || mysqli_num_rows($reportQuery) === 0) {
    die("Access denied: File not found or not authorized");
}

$report = mysqli_fetch_assoc($reportQuery);

/* ================= CONSTRUCT FULL PATH ================= */
// The database stores paths like "../uploads/reports/filename"
// We need to go up one level from patient directory to access it
$fullPath = __DIR__ . '/' . $filePath;

// Debug information (remove in production)
error_log("Original file path: " . $filePath);
error_log("Full path: " . $fullPath);
error_log("File exists: " . (file_exists($fullPath) ? 'Yes' : 'No'));

if (!file_exists($fullPath)) {
    die("File not found at: " . htmlspecialchars($fullPath) . "<br>Original path: " . htmlspecialchars($filePath));
}

/* ================= GET FILE INFO ================= */
$fileInfo = pathinfo($fullPath);
$extension = strtolower($fileInfo['extension']);

if ($extension !== 'pdf') {
    die("Invalid file type");
}

/* ================= OUTPUT PDF ================= */
$isDownload = isset($_GET['download']) && $_GET['download'] == '1';

if ($isDownload) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . htmlspecialchars($report['file_name']) . '"');
} else {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . htmlspecialchars($report['file_name']) . '"');
}

header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($fullPath);
exit;
?>
