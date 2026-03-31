<?php
include "db.php";

echo "=== Checking Medical Tables ===\n";

// Check if doctor_medical_history exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'doctor_medical_history'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ doctor_medical_history table exists\n";
    
    // Check if it has any data
    $count = mysqli_query($conn, "SELECT COUNT(*) as count FROM doctor_medical_history");
    $row = mysqli_fetch_assoc($count);
    echo "📊 Records in doctor_medical_history: " . $row['count'] . "\n";
} else {
    echo "❌ doctor_medical_history table does NOT exist\n";
}

// Check if medical_bills exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'medical_bills'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ medical_bills table exists\n";
    
    // Check if it has any data
    $count = mysqli_query($conn, "SELECT COUNT(*) as count FROM medical_bills");
    $row = mysqli_fetch_assoc($count);
    echo "📊 Records in medical_bills: " . $row['count'] . "\n";
} else {
    echo "❌ medical_bills table does NOT exist\n";
}

// Check patient_disease_history
$result = mysqli_query($conn, "SHOW TABLES LIKE 'patient_disease_history'");
if (mysqli_num_rows($result) > 0) {
    echo "✅ patient_disease_history table exists\n";
    
    // Check if it has any data
    $count = mysqli_query($conn, "SELECT COUNT(*) as count FROM patient_disease_history");
    $row = mysqli_fetch_assoc($count);
    echo "📊 Records in patient_disease_history: " . $row['count'] . "\n";
} else {
    echo "❌ patient_disease_history table does NOT exist\n";
}

echo "\n=== All Tables ===\n";
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    if (strpos($row[0], 'medical') !== false || strpos($row[0], 'doctor') !== false || strpos($row[0], 'bill') !== false || strpos($row[0], 'disease') !== false) {
        echo "📋 " . $row[0] . "\n";
    }
}
?>
