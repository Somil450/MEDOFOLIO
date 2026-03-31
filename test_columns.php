<?php
$conn = new mysqli('localhost', 'root', '', 'healthintel');
echo "Testing doctor_medical_history columns:\n";

// Try to run the query and show the actual error
try {
    $result = $conn->query("SELECT * FROM doctor_medical_history LIMIT 1");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Available columns:\n";
        foreach ($row as $key => $value) {
            echo "- $key\n";
        }
    } else {
        echo "Query failed: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$conn->close();
?>
