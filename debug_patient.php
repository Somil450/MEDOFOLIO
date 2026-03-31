<?php
session_start();
include "db.php";

echo "<h2>Patient Debug Info</h2>";

if (isset($_SESSION['user_id'])) {
    $patient_id = (int) $_SESSION['user_id'];
    echo "Logged in patient_id: " . $patient_id . "<br>";
} else {
    echo "Not logged in<br>";
    echo "Setting patient_id = 1 for testing<br>";
    $patient_id = 1;
    $_SESSION['user_id'] = 1;
}

echo "<h3>Testing Medical History Query for patient_id $patient_id:</h3>";

$q = mysqli_query($conn,"
    SELECT 
        d.disease_name as disease_name, 
        h.detected_date, 
        h.status, 
        h.severity_level,
        'Medofolio Patient' as source
    FROM patient_disease_history h
    JOIN disease_master d ON h.disease_id=d.disease_id
    WHERE h.patient_id=$patient_id
    
    UNION ALL
    
    SELECT 
        dmh.condition_name as disease_name,
        dmh.diagnosis_date as detected_date,
        dmh.status,
        dmh.severity as severity_level,
        'Doctor Added' as source
    FROM doctor_medical_history dmh
    WHERE dmh.patient_id=$patient_id
    
    ORDER BY detected_date DESC
");

if ($q) {
    $count = mysqli_num_rows($q);
    echo "Found $count medical records<br>";
    
    while ($row = mysqli_fetch_assoc($q)) {
        echo "🏥 " . $row['disease_name'] . " - " . $row['status'] . " (" . $row['source'] . ")<br>";
    }
} else {
    echo "❌ Query failed: " . mysqli_error($conn) . "<br>";
}

echo "<h3>Testing Bills Query for patient_id $patient_id:</h3>";

$bills = mysqli_query($conn,"
    SELECT mb.*, dl.name as doctor_name
    FROM medical_bills mb
    LEFT JOIN doctor_login dl ON mb.doctor_id = dl.doctor_id
    WHERE mb.patient_id=$patient_id 
    ORDER BY mb.billing_date DESC
");

if ($bills) {
    $count = mysqli_num_rows($bills);
    echo "Found $count bills<br>";
    
    while ($bill = mysqli_fetch_assoc($bills)) {
        echo "💰 " . $bill['description'] . " - ₹" . $bill['total_amount'] . " (" . $bill['payment_status'] . ")<br>";
    }
} else {
    echo "❌ Bills query failed: " . mysqli_error($conn) . "<br>";
}

echo "<p><a href='healthintel/patient/patient_profile.php'>Go to Patient Profile</a></p>";
echo "<p><a href='healthintel/patient/login.php'>Login as Patient</a></p>";
?>
