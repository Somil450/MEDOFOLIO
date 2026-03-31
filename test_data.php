<?php
include "db.php";

echo "<h2>Checking Database Status</h2>";

// Check doctor_medical_history
$result = mysqli_query($conn, "SHOW TABLES LIKE 'doctor_medical_history'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ doctor_medical_history table exists<br>";
    
    $count = mysqli_query($conn, "SELECT COUNT(*) as count FROM doctor_medical_history");
    $row = mysqli_fetch_assoc($count);
    echo "📊 Records: " . $row['count'] . "<br>";
    
    if ($row['count'] > 0) {
        $data = mysqli_query($conn, "SELECT * FROM doctor_medical_history LIMIT 3");
        while ($d = mysqli_fetch_assoc($data)) {
            echo "📋 Sample: " . $d['condition_name'] . " - " . $d['status'] . "<br>";
        }
    }
} else {
    echo "❌ doctor_medical_history table missing<br>";
}

// Check medical_bills
$result = mysqli_query($conn, "SHOW TABLES LIKE 'medical_bills'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ medical_bills table exists<br>";
    
    $count = mysqli_query($conn, "SELECT COUNT(*) as count FROM medical_bills");
    $row = mysqli_fetch_assoc($count);
    echo "📊 Records: " . $row['count'] . "<br>";
    
    if ($row['count'] > 0) {
        $data = mysqli_query($conn, "SELECT * FROM medical_bills LIMIT 3");
        while ($d = mysqli_fetch_assoc($data)) {
            echo "💰 Sample: " . $d['description'] . " - ₹" . $d['total_amount'] . " (" . $d['payment_status'] . ")<br>";
        }
    }
} else {
    echo "❌ medical_bills table missing<br>";
}

// Check patient_disease_history
$result = mysqli_query($conn, "SHOW TABLES LIKE 'patient_disease_history'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ patient_disease_history table exists<br>";
    
    $count = mysqli_query($conn, "SELECT COUNT(*) as count FROM patient_disease_history");
    $row = mysqli_fetch_assoc($count);
    echo "📊 Records: " . $row['count'] . "<br>";
} else {
    echo "❌ patient_disease_history table missing<br>";
}

echo "<h2>Test Patient Query</h2>";
$patient_id = 1;
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
    LIMIT 5
");

if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        echo "🏥 " . $row['disease_name'] . " - " . $row['status'] . " (" . $row['source'] . ")<br>";
    }
} else {
    echo "❌ Query failed: " . mysqli_error($conn) . "<br>";
}

echo "<p><a href='healthintel/patient/patient_profile.php'>Test Patient Profile</a></p>";
?>
