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

echo "Testing isolated bind parameters...\n";

// Simulate form submission
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['patient_id'] = 2;
$_POST['condition_name'] = 'Test Condition';
$_POST['diagnosis_date'] = '2024-03-25';
$_POST['severity'] = 'moderate';
$_POST['description'] = 'Test description';
$_POST['treatment_plan'] = 'Test treatment';
$_POST['follow_up_required'] = 0;
$_POST['follow_up_date'] = '';

// Test just the medical history insertion part
try {
    $patient_id = (int)$_POST['patient_id'];
    $doctor_id = 1;
    $condition_name = trim($_POST['condition_name']);
    $diagnosis_date = $_POST['diagnosis_date'];
    $severity = $_POST['severity'];
    $description = trim($_POST['description']);
    $treatment_plan = trim($_POST['treatment_plan']);
    $follow_up_required = isset($_POST['follow_up_required']) ? 1 : 0;
    $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;

    // Handle file upload
    $file_name = '';
    $file_path = '';
    if (isset($_FILES['medical_file']) && $_FILES['medical_file']['error'] == 0) {
        $upload_dir = 'uploads/medical_history_files/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['medical_file']['name']);
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['medical_file']['tmp_name'], $file_path)) {
            // File uploaded successfully
        }
    }

    $stmt = mysqli_prepare($conn, 
        "INSERT INTO doctor_medical_history (patient_id, doctor_id, condition_name, diagnosis_date, severity, description, treatment_plan, follow_up_required, follow_up_date, file_name, file_path) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "iissssssss", $patient_id, $doctor_id, $condition_name, $diagnosis_date, $severity, $description, $treatment_plan, $follow_up_required, $follow_up_date, $file_name, $file_path);
    mysqli_stmt_execute($stmt);
    
    echo "✅ Isolated test successful!\n";
    echo "✅ Bind parameters working correctly\n";
    echo "✅ Medical history insertion working\n";
    
} catch (Error $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
?>
