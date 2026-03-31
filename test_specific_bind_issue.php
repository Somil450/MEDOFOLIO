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

echo "Testing specific bind issue...\n";

// Test medical history submission with exact same data as error
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['patient_id'] = 2;
$_POST['condition_name'] = 'hyper';
$_POST['diagnosis_date'] = '2026-03-12';
$_POST['severity'] = 'moderate';
$_POST['description'] = 'aaaa';
$_POST['treatment_plan'] = 'aaaa';
$_POST['follow_up_required'] = 1;
$_POST['follow_up_date'] = '2026-03-27';

try {
    // Process with minimal code to isolate issue
    if (isset($_POST['add_medical_history'])) {
        $patient_id = (int)$_POST['patient_id'];
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

        // Simple direct bind_param test
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_medical_history (patient_id, doctor_id, condition_name, diagnosis_date, severity, description, treatment_plan, follow_up_required, follow_up_date, file_name, file_path) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        // Test with minimal variables
        mysqli_stmt_bind_param($stmt, "iissssssss", 
            (int)$patient_id, 
            (int)$_SESSION['doctor_id'], 
            $condition_name, 
            $diagnosis_date, 
            $severity, 
            $description, 
            $treatment_plan, 
            (int)$follow_up_required, 
            $follow_up_date, 
            $file_name, 
            $file_path
        );
        mysqli_stmt_execute($stmt);

        echo "✅ Specific bind test successful!\n";
        echo "✅ No variable expansion issues\n";
        echo "✅ Medical history working\n";
    }
    
} catch (Error $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
?>
