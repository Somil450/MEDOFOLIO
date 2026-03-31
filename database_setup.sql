-- HealthIntel Doctor Module Database Setup
-- Run these queries to create the doctor module tables

-- 1. Doctor Login Table
CREATE TABLE IF NOT EXISTS doctor_login (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    hospital_name VARCHAR(150) NULL,
    license_number VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- 2. Doctor Notes Table
CREATE TABLE IF NOT EXISTS doctor_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    diagnosis TEXT NOT NULL,
    prescription TEXT NOT NULL,
    follow_up_date DATE NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id)
);

-- 3. Patient Access Table (Doctor-Patient Assignment)
CREATE TABLE IF NOT EXISTS patient_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NULL,
    access_level ENUM('full', 'read_only', 'emergency') DEFAULT 'full',
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id),
    UNIQUE KEY unique_patient_doctor (patient_id, doctor_id)
);

-- 4. Alerts Table
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    message TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    alert_type ENUM('vital', 'medication', 'appointment', 'emergency', 'system') DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id)
);

-- 5. Patient Risk Scores Table
CREATE TABLE IF NOT EXISTS patient_risk_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    risk_score DECIMAL(5,2) NOT NULL,
    risk_factors JSON NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id)
);

-- 6. Doctor Activity Log
CREATE TABLE IF NOT EXISTS doctor_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    patient_id INT NULL,
    activity_type ENUM('login', 'view_patient', 'add_note', 'create_alert', 'assign_patient') NOT NULL,
    activity_details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctor_login(doctor_id),
    FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
);

-- Insert sample doctors for testing
INSERT INTO doctor_login (name, email, password, specialization, hospital_name) VALUES 
('Dr. Sarah Johnson', 'sarah.j@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cardiologist', 'City General Hospital'),
('Dr. Michael Chen', 'michael.c@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Neurologist', 'City General Hospital'),
('Dr. Emily Davis', 'emily.d@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pediatrician', 'Children\'s Medical Center')
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- Create indexes for better performance
CREATE INDEX idx_doctor_notes_patient ON doctor_notes(patient_id);
CREATE INDEX idx_doctor_notes_doctor ON doctor_notes(doctor_id);
CREATE INDEX idx_patient_access_patient ON patient_access(patient_id);
CREATE INDEX idx_patient_access_doctor ON patient_access(doctor_id);
CREATE INDEX idx_alerts_patient ON alerts(patient_id);
CREATE INDEX idx_alerts_doctor ON alerts(doctor_id);
CREATE INDEX idx_alerts_severity ON alerts(severity);
CREATE INDEX idx_risk_scores_patient ON patient_risk_scores(patient_id);
CREATE INDEX idx_activity_log_doctor ON doctor_activity_log(doctor_id);
CREATE INDEX idx_activity_log_date ON doctor_activity_log(created_at);
