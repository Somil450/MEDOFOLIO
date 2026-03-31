<?php
$conn = new mysqli('localhost', 'root', '', 'healthintel');
$result = $conn->query('DESCRIBE doctor_medical_history');
echo "doctor_medical_history table structure:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
$conn->close();
?>
