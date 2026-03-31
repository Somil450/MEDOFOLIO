<?php
// Direct database connection
$conn = mysqli_connect('localhost', 'root', '', 'healthintel');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Creating medical_history table from scratch...\n";

// Create the table with simple approach
$create_table_query = "
CREATE TABLE `medical_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `patient_id` int(11) NOT NULL,
    `doctor_id` int(11) NOT NULL,
    `condition_name` varchar(200) NOT NULL,
    `diagnosis_date` date NOT NULL,
    `severity` enum('mild','moderate','severe','critical') NOT NULL,
    `status` enum('active','resolved','chronic','managed') NOT NULL DEFAULT 'active',
    `description` text,
    `treatment_plan` text,
    `file_name` varchar(255) DEFAULT NULL,
    `file_path` varchar(500) DEFAULT NULL,
    `follow_up_required` tinyint(1) DEFAULT 0,
    `follow_up_date` date DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_patient_id` (`patient_id`),
    KEY `idx_doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $create_table_query)) {
    echo "✅ medical_history table created successfully!\n";
    
    // Add some sample data for testing
    mysqli_query($conn, "INSERT INTO `medical_history` (patient_id, doctor_id, condition_name, diagnosis_date, severity, description, treatment_plan) VALUES (2, 1, 'Test Condition', '2024-03-25', 'moderate', 'Test description for medical history', 'Test treatment plan')");
    
} else {
    echo "❌ Error creating table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);

echo "✅ Medical history table is now ready!\n";
?>
