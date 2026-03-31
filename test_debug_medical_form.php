<?php
session_start();
include "db.php";

// Simulate logged in doctor
$_SESSION['doctor_id'] = 1;
$_SESSION['doctor_name'] = 'Test Doctor';
$_SESSION['doctor_specialization'] = 'General Physician';
$_SESSION['doctor_hospital'] = 'Test Hospital';

// Test patient
$_GET['patient_id'] = 2;

echo "Testing medical history form with debug...\n";

// Simulate form submission
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['patient_id'] = 2;
$_POST['condition_name'] = 'Test Condition';
$_POST['diagnosis_date'] = '2024-03-25';
$_POST['severity'] = 'moderate';
$_POST['description'] = 'Test description';
$_POST['treatment_plan'] = 'Test treatment';

try {
    include "dashboard/doctor_dashboard.php";
    echo "✅ Form submission simulated!\n";
} catch (Error $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
?>
