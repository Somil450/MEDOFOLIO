<?php
$conn = new mysqli('localhost', 'root', '', 'healthintel');
echo "Checking doctor_medical_history table structure...\n";
$result = $conn->query('DESCRIBE doctor_medical_history');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
$conn->close();
?>
