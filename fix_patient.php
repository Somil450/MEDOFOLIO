<?php
include "db.php";

// Create tables if they don't exist
echo "<h2>Creating Tables...</h2>";

// Create doctor_medical_history
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql1);

// Create medical_bills
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql2);

// Create medical_reports
$sql3 = "CREATE TABLE IF NOT EXISTS medical_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    disease_name VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql3);

echo "✅ Tables created<br>";

// Add sample data for patient_id = 1
echo "<h2>Adding Sample Data...</h2>";

// Add doctor medical history
mysqli_query($conn, "DELETE FROM doctor_medical_history WHERE patient_id = 1");
mysqli_query($conn, "
    INSERT INTO doctor_medical_history 
    (patient_id, doctor_id, condition_name, diagnosis_date, status, severity, description, treatment_plan) 
    VALUES (1, 1, 'Hypertension', '2026-02-23', 'Active', '3', 'Patient diagnosed with stage 2 hypertension', 'Prescribed ACE inhibitors and lifestyle modifications')
");

mysqli_query($conn, "
    INSERT INTO doctor_medical_history 
    (patient_id, doctor_id, condition_name, diagnosis_date, status, severity, description, treatment_plan) 
    VALUES (1, 1, 'Diabetes Type 2', '2026-02-20', 'Active', '2', 'Type 2 diabetes diagnosed', 'Metformin prescribed, diet and exercise recommended')
");

// Add medical bills
mysqli_query($conn, "DELETE FROM medical_bills WHERE patient_id = 1");
mysqli_query($conn, "
    INSERT INTO medical_bills 
    (patient_id, doctor_id, bill_type, description, total_amount, billing_date, due_date, payment_status) 
    VALUES (1, 1, 'consultation', 'Cardiology consultation for hypertension', 1500.00, '2026-02-23', '2026-03-10', 'paid')
");

mysqli_query($conn, "
    INSERT INTO medical_bills 
    (patient_id, doctor_id, bill_type, description, total_amount, billing_date, due_date, payment_status) 
    VALUES (1, 1, 'lab_test', 'Blood pressure monitoring and lipid profile', 800.00, '2026-02-25', '2026-03-15', 'pending')
");

mysqli_query($conn, "
    INSERT INTO medical_bills 
    (patient_id, doctor_id, bill_type, description, total_amount, billing_date, due_date, payment_status) 
    VALUES (1, 1, 'medication', 'Diabetes medication prescription', 1200.00, '2026-02-20', '2026-03-05', 'pending')
");

echo "✅ Sample data added<br>";

// Test the query
echo "<h2>Testing Query...</h2>";
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
");

echo "<h3>Medical History Results:</h3>";
while ($row = mysqli_fetch_assoc($q)) {
    echo "🏥 " . $row['disease_name'] . " - " . $row['status'] . " (" . $row['source'] . ")<br>";
}

// Test bills query
$bills = mysqli_query($conn,"
    SELECT mb.*, dl.name as doctor_name
    FROM medical_bills mb
    LEFT JOIN doctor_login dl ON mb.doctor_id = dl.doctor_id
    WHERE mb.patient_id=$patient_id 
    ORDER BY mb.billing_date DESC
");

echo "<h3>Medical Bills Results:</h3>";
while ($bill = mysqli_fetch_assoc($bills)) {
    echo "💰 " . $bill['description'] . " - ₹" . $bill['total_amount'] . " (" . $bill['payment_status'] . ")<br>";
}

echo "<h2>✅ Setup Complete!</h2>";
echo "<p><a href='healthintel/patient/patient_profile.php' target='_blank'>Test Patient Profile Now</a></p>";
?>
