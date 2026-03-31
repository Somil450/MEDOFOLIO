<?php
// Quick database setup for HealthIntel Doctor Module
echo "Starting database setup...\n";

try {
    $conn = new mysqli("localhost", "root", "", "healthintel");
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to database successfully\n";
    
    // Create doctor_login table
    $sql1 = "CREATE TABLE IF NOT EXISTS doctor_login (
        doctor_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        specialization VARCHAR(100) NOT NULL,
        hospital_name VARCHAR(150) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE
    )";
    
    if ($conn->query($sql1)) {
        echo "✓ Created doctor_login table\n";
    } else {
        echo "Error creating doctor_login: " . $conn->error . "\n";
    }
    
    // Create doctor_notes table
    $sql2 = "CREATE TABLE IF NOT EXISTS doctor_notes (
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
    )";
    
    if ($conn->query($sql2)) {
        echo "✓ Created doctor_notes table\n";
    } else {
        echo "Error creating doctor_notes: " . $conn->error . "\n";
    }
    
    // Create patient_access table
    $sql3 = "CREATE TABLE IF NOT EXISTS patient_access (
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
    )";
    
    if ($conn->query($sql3)) {
        echo "✓ Created patient_access table\n";
    } else {
        echo "Error creating patient_access: " . $conn->error . "\n";
    }
    
    // Create alerts table
    $sql4 = "CREATE TABLE IF NOT EXISTS alerts (
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
    )";
    
    if ($conn->query($sql4)) {
        echo "✓ Created alerts table\n";
    } else {
        echo "Error creating alerts: " . $conn->error . "\n";
    }
    
    // Insert sample doctors
    $doctors = [
        ['name' => 'Dr. Sarah Johnson', 'email' => 'sarah.j@hospital.com', 'password' => password_hash('password', PASSWORD_DEFAULT), 'specialization' => 'Cardiologist', 'hospital_name' => 'City General Hospital'],
        ['name' => 'Dr. Michael Chen', 'email' => 'michael.c@hospital.com', 'password' => password_hash('password', PASSWORD_DEFAULT), 'specialization' => 'Neurologist', 'hospital_name' => 'City General Hospital'],
        ['name' => 'Dr. Emily Davis', 'email' => 'emily.d@hospital.com', 'password' => password_hash('password', PASSWORD_DEFAULT), 'specialization' => 'Pediatrician', 'hospital_name' => 'Children\'s Medical Center']
    ];
    
    foreach ($doctors as $doctor) {
        $stmt = $conn->prepare("INSERT IGNORE INTO doctor_login (name, email, password, specialization, hospital_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $doctor['name'], $doctor['email'], $doctor['password'], $doctor['specialization'], $doctor['hospital_name']);
        $stmt->execute();
    }
    
    echo "✓ Inserted sample doctors\n";
    echo "\n🎉 Database setup completed successfully!\n";
    echo "You can now access:\n";
    echo "Doctor Login: http://10.70.79.30/healthintel/auth/doctor_login.php\n";
    echo "Demo credentials: sarah.j@hospital.com / password\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
