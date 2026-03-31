<?php
include "db.php";

echo "=== Adding Sample Data ===\n";

// Get a patient ID (assuming patient_id = 1 exists)
$patient_id = 1;

// Get a doctor ID (assuming doctor_id = 1 exists)
$doctor_id = 1;

// Add sample doctor medical history
$sql1 = "INSERT INTO doctor_medical_history 
        (patient_id, doctor_id, condition_name, diagnosis_date, status, severity, description, treatment_plan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt1 = mysqli_prepare($conn, $sql1);
$condition_name = "Hypertension";
$diagnosis_date = date('Y-m-d', strtotime('-30 days'));
$status = "Active";
$severity = "3";
$description = "Patient diagnosed with stage 2 hypertension";
$treatment_plan = "Prescribed ACE inhibitors and lifestyle modifications";

mysqli_stmt_bind_param($stmt1, "iisssss", $patient_id, $doctor_id, $condition_name, $diagnosis_date, $status, $severity, $description, $treatment_plan);

if (mysqli_stmt_execute($stmt1)) {
    echo "✅ Sample doctor medical history added\n";
} else {
    echo "❌ Error adding doctor history: " . mysqli_error($conn) . "\n";
}

// Add sample medical bill
$sql2 = "INSERT INTO medical_bills 
        (patient_id, doctor_id, bill_type, description, total_amount, billing_date, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt2 = mysqli_prepare($conn, $sql2);
$bill_type = "consultation";
$description = "Cardiology consultation for hypertension";
$total_amount = 1500.00;
$billing_date = date('Y-m-d', strtotime('-30 days'));
$due_date = date('Y-m-d', strtotime('-15 days'));

mysqli_stmt_bind_param($stmt2, "iissdds", $patient_id, $doctor_id, $bill_type, $description, $total_amount, $billing_date, $due_date);

if (mysqli_stmt_execute($stmt2)) {
    echo "✅ Sample medical bill added\n";
} else {
    echo "❌ Error adding medical bill: " . mysqli_error($conn) . "\n";
}

// Add another sample bill (pending)
$sql3 = "INSERT INTO medical_bills 
        (patient_id, doctor_id, bill_type, description, total_amount, billing_date, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt3 = mysqli_prepare($conn, $sql3);
$bill_type2 = "lab_test";
$description2 = "Blood pressure monitoring and lipid profile";
$total_amount2 = 800.00;
$billing_date2 = date('Y-m-d', strtotime('-7 days'));
$due_date2 = date('Y-m-d', strtotime('+7 days'));

mysqli_stmt_bind_param($stmt3, "iissdds", $patient_id, $doctor_id, $bill_type2, $description2, $total_amount2, $billing_date2, $due_date2);

if (mysqli_stmt_execute($stmt3)) {
    echo "✅ Sample pending medical bill added\n";
} else {
    echo "❌ Error adding pending medical bill: " . mysqli_error($conn) . "\n";
}

echo "\n=== Sample Data Added ===\n";
echo "Now visit the patient profile to see:\n";
echo "1. Doctor-added medical history\n";
echo "2. Medical bills (both paid and pending)\n";
echo "3. Payment functionality for pending bills\n";
?>
