<?php
include "db.php";

echo "=== Creating Missing Tables ===\n";

// Create doctor_medical_history table if it doesn't exist
$sql1 = "CREATE TABLE IF NOT EXISTS doctor_medical_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT,
    condition_name VARCHAR(255) NOT NULL,
    diagnosis_date DATE NOT NULL,
    status ENUM('Critical', 'Active', 'Recovered', 'Stable') DEFAULT 'Active',
    severity ENUM('1', '2', '3', '4', '5') DEFAULT '3',
    description TEXT,
    treatment_plan TEXT,
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id)
)";

if (mysqli_query($conn, $sql1)) {
    echo "✅ doctor_medical_history table created/verified\n";
} else {
    echo "❌ Error creating doctor_medical_history: " . mysqli_error($conn) . "\n";
}

// Create medical_bills table if it doesn't exist
$sql2 = "CREATE TABLE IF NOT EXISTS medical_bills (
    bill_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT,
    order_id INT,
    bill_type ENUM('consultation', 'medication', 'lab_test', 'procedure', 'emergency') DEFAULT 'consultation',
    description VARCHAR(500) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    billing_date DATE NOT NULL,
    due_date DATE NOT NULL,
    payment_status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    paid_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id)
)";

if (mysqli_query($conn, $sql2)) {
    echo "✅ medical_bills table created/verified\n";
} else {
    echo "❌ Error creating medical_bills: " . mysqli_error($conn) . "\n";
}

// Create medical_reports table if it doesn't exist
$sql3 = "CREATE TABLE IF NOT EXISTS medical_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    disease_name VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
)";

if (mysqli_query($conn, $sql3)) {
    echo "✅ medical_reports table created/verified\n";
} else {
    echo "❌ Error creating medical_reports: " . mysqli_error($conn) . "\n";
}

echo "\n=== Tables Created Successfully ===\n";
echo "Now the patient profile should show:\n";
echo "1. 2-way data synchronization (patient + doctor entries)\n";
echo "2. Payment functionality\n";
echo "3. Medical reports upload/view\n";
?>
