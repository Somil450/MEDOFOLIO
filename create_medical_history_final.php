<?php
// Direct database connection
$conn = mysqli_connect('localhost', 'root', '', 'healthintel');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Creating medical_history table properly...\n";

// Create the table with correct structure
$create_table_query = "
CREATE TABLE medical_history (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    condition_name VARCHAR(200) NOT NULL,
    diagnosis_date DATE NOT NULL,
    severity ENUM('mild', 'moderate', 'severe', 'critical') NOT NULL,
    status ENUM('active', 'resolved', 'chronic', 'managed') NOT NULL DEFAULT 'active',
    description TEXT,
    treatment_plan TEXT,
    file_name VARCHAR(255) NULL,
    file_path VARCHAR(500) NULL,
    follow_up_required TINYINT(1) DEFAULT 0,
    follow_up_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $create_table_query)) {
    echo "✅ medical_history table created successfully!\n";
} else {
    echo "❌ Error creating table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);

echo "✅ Table is now ready for medical history operations!\n";
?>
