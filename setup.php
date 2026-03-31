<?php
include "db.php";

echo "<h2>Creating Missing Tables...</h2>";

// Create doctor_medical_history table
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
    echo "✅ doctor_medical_history table created<br>";
}

// Create medical_bills table
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
    echo "✅ medical_bills table created<br>";
}

// Create medical_reports table
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
    echo "✅ medical_reports table created<br>";
}

echo "<h2>Adding Sample Data...</h2>";

// Get a patient ID
$patient_id = 1;
$doctor_id = 1;

// Add sample doctor medical history
$sql4 = "INSERT INTO doctor_medical_history 
        (patient_id, doctor_id, condition_name, diagnosis_date, status, severity, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt1 = mysqli_prepare($conn, $sql4);
$condition_name = "Hypertension";
$diagnosis_date = date('Y-m-d', strtotime('-30 days'));
$status = "Active";
$severity = "3";
$description = "Patient diagnosed with stage 2 hypertension";
$treatment_plan = "Prescribed ACE inhibitors and lifestyle modifications";

mysqli_stmt_bind_param($stmt1, "iisssss", $patient_id, $doctor_id, $condition_name, $diagnosis_date, $status, $severity, $description);
mysqli_stmt_execute($stmt1);

echo "✅ Sample doctor medical history added<br>";

// Add sample medical bills
$sql5 = "INSERT INTO medical_bills 
        (patient_id, doctor_id, bill_type, description, total_amount, billing_date, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt2 = mysqli_prepare($conn, $sql5);
$bill_type = "consultation";
$description = "Cardiology consultation for hypertension";
$total_amount = 1500.00;
$billing_date = date('Y-m-d', strtotime('-30 days'));
$due_date = date('Y-m-d', strtotime('-15 days'));

mysqli_stmt_bind_param($stmt2, "iissdds", $patient_id, $doctor_id, $bill_type, $description, $total_amount, $billing_date, $due_date);
mysqli_stmt_execute($stmt2);

echo "✅ Sample medical bill added<br>";

// Add pending bill
$bill_type2 = "lab_test";
$description2 = "Blood pressure monitoring and lipid profile";
$total_amount2 = 800.00;
$billing_date2 = date('Y-m-d', strtotime('-7 days'));
$due_date2 = date('Y-m-d', strtotime('+7 days'));

mysqli_stmt_bind_param($stmt2, "iissdds", $patient_id, $doctor_id, $bill_type2, $description2, $total_amount2, $billing_date2, $due_date2);
mysqli_stmt_execute($stmt2);

echo "✅ Sample pending medical bill added<br>";

echo "<h2>✅ Setup Complete!</h2>";
echo "<p><a href='healthintel/patient/patient_profile.php'>Test Patient Profile</a></p>";
?>
