<?php
include "db.php";

echo "=== Creating Medical Management Tables ===\n";

// Create medical history table
$sql1 = "CREATE TABLE IF NOT EXISTS medical_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    condition_name VARCHAR(200) NOT NULL,
    diagnosis_date DATE NOT NULL,
    severity ENUM('mild', 'moderate', 'severe', 'critical') DEFAULT 'moderate',
    status ENUM('active', 'resolved', 'chronic', 'managed') DEFAULT 'active',
    description TEXT,
    treatment_plan TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id),
    INDEX idx_patient_history (patient_id),
    INDEX idx_doctor_history (doctor_id)
)";

if (mysqli_query($conn, $sql1)) {
    echo "✅ Medical history table created\n";
} else {
    echo "❌ Error creating medical history: " . mysqli_error($conn) . "\n";
}

// Create medications table
$sql2 = "CREATE TABLE IF NOT EXISTS medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    medication_name VARCHAR(200) NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    route ENUM('oral', 'injectable', 'topical', 'inhalation', 'other') DEFAULT 'oral',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active', 'completed', 'discontinued', 'on_hold') DEFAULT 'active',
    purpose TEXT,
    side_effects TEXT,
    prescribed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id),
    FOREIGN KEY (prescribed_by) REFERENCES doctor_login(doctor_id),
    INDEX idx_patient_medications (patient_id),
    INDEX idx_doctor_medications (doctor_id)
)";

if (mysqli_query($conn, $sql2)) {
    echo "✅ Medications table created\n";
} else {
    echo "❌ Error creating medications: " . mysqli_error($conn) . "\n";
}

// Create vitals table
$sql3 = "CREATE TABLE IF NOT EXISTS vitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    blood_pressure_systolic INT,
    blood_pressure_diastolic INT,
    heart_rate INT,
    temperature DECIMAL(4,1),
    weight DECIMAL(5,1),
    height DECIMAL(5,1),
    oxygen_saturation INT,
    blood_sugar DECIMAL(5,1),
    notes TEXT,
    measured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id),
    FOREIGN KEY (created_by) REFERENCES doctor_login(doctor_id),
    INDEX idx_patient_vitals (patient_id),
    INDEX idx_doctor_vitals (doctor_id),
    INDEX idx_measured_at (measured_at)
)";

if (mysqli_query($conn, $sql3)) {
    echo "✅ Vitals table created\n";
} else {
    echo "❌ Error creating vitals: " . mysqli_error($conn) . "\n";
}

// Create allergies table
$sql4 = "CREATE TABLE IF NOT EXISTS allergies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    allergen VARCHAR(200) NOT NULL,
    allergy_type ENUM('drug', 'food', 'environmental', 'other') DEFAULT 'drug',
    severity ENUM('mild', 'moderate', 'severe', 'life_threatening') DEFAULT 'moderate',
    reaction TEXT,
    status ENUM('active', 'resolved') DEFAULT 'active',
    notes TEXT,
    diagnosed_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id),
    INDEX idx_patient_allergies (patient_id)
)";

if (mysqli_query($conn, $sql4)) {
    echo "✅ Allergies table created\n";
} else {
    echo "❌ Error creating allergies: " . mysqli_error($conn) . "\n";
}

// Create lab_results table
$sql5 = "CREATE TABLE IF NOT EXISTS lab_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    test_name VARCHAR(200) NOT NULL,
    test_type VARCHAR(100) NOT NULL,
    result_value VARCHAR(500),
    normal_range VARCHAR(200),
    status ENUM('normal', 'abnormal', 'critical') DEFAULT 'normal',
    notes TEXT,
    test_date DATE NOT NULL,
    reported_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id),
    INDEX idx_patient_lab_results (patient_id),
    INDEX idx_test_date (test_date)
)";

if (mysqli_query($conn, $sql5)) {
    echo "✅ Lab results table created\n";
} else {
    echo "❌ Error creating lab results: " . mysqli_error($conn) . "\n";
}

// Create immunizations table
$sql6 = "CREATE TABLE IF NOT EXISTS immunizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    vaccine_name VARCHAR(200) NOT NULL,
    vaccine_type VARCHAR(100),
    dose_number INT,
    administration_date DATE NOT NULL,
    next_due_date DATE,
    administered_by VARCHAR(100),
    batch_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id),
    INDEX idx_patient_immunizations (patient_id),
    INDEX idx_administration_date (administration_date)
)";

if (mysqli_query($conn, $sql6)) {
    echo "✅ Immunizations table created\n";
} else {
    echo "❌ Error creating immunizations: " . mysqli_error($conn) . "\n";
}

echo "\n=== All Medical Tables Created Successfully ===\n";
?>
