<!DOCTYPE html>
<html>
<head>
    <title>HealthIntel Doctor Module - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #4A90E2; text-align: center; margin-bottom: 30px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn { background: #4A90E2; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #357ABD; }
        .result { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        pre { background: #f1f1f1; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 HealthIntel Doctor Module Setup</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
            echo "<div class='status info'>Setting up database tables...</div>";
            
            try {
                include "db.php";
                
                // Check connection
                if (!$conn) {
                    throw new Exception("Database connection failed");
                }
                
                $results = [];
                
                // Create doctor_login table
                $sql1 = "CREATE TABLE IF NOT EXISTS doctor_login (
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
                )";
                
                if ($conn->query($sql1)) {
                    $results[] = "✓ Created doctor_login table";
                } else {
                    $results[] = "✗ Error creating doctor_login: " . $conn->error;
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
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                
                if ($conn->query($sql2)) {
                    $results[] = "✓ Created doctor_notes table";
                } else {
                    $results[] = "✗ Error creating doctor_notes: " . $conn->error;
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
                    UNIQUE KEY unique_patient_doctor (patient_id, doctor_id)
                )";
                
                if ($conn->query($sql3)) {
                    $results[] = "✓ Created patient_access table";
                } else {
                    $results[] = "✗ Error creating patient_access: " . $conn->error;
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
                    resolved_by INT NULL
                )";
                
                if ($conn->query($sql4)) {
                    $results[] = "✓ Created alerts table";
                } else {
                    $results[] = "✗ Error creating alerts: " . $conn->error;
                }
                
                // Insert sample doctors
                $doctors = [
                    ['name' => 'Dr. Sarah Johnson', 'email' => 'sarah.j@hospital.com', 'password' => password_hash('password', PASSWORD_DEFAULT), 'specialization' => 'Cardiologist', 'hospital_name' => 'City General Hospital', 'license_number' => 'MD-123456', 'phone' => '+1-555-123-4567'],
                    ['name' => 'Dr. Michael Chen', 'email' => 'michael.c@hospital.com', 'password' => password_hash('password', PASSWORD_DEFAULT), 'specialization' => 'Neurologist', 'hospital_name' => 'City General Hospital', 'license_number' => 'MD-789012', 'phone' => '+1-555-987-6543'],
                    ['name' => 'Dr. Emily Davis', 'email' => 'emily.d@hospital.com', 'password' => password_hash('password', PASSWORD_DEFAULT), 'specialization' => 'Pediatrician', 'hospital_name' => 'Children\'s Medical Center', 'license_number' => 'MD-345678', 'phone' => '+1-555-234-5678']
                ];
                
                foreach ($doctors as $doctor) {
                    $stmt = $conn->prepare("INSERT IGNORE INTO doctor_login (name, email, password, specialization, hospital_name, license_number, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $doctor['name'], $doctor['email'], $doctor['password'], $doctor['specialization'], $doctor['hospital_name'], $doctor['license_number'], $doctor['phone']);
                    $stmt->execute();
                }
                $results[] = "✓ Inserted sample doctors";
                
                echo "<div class='status success'>✅ Database setup completed successfully!</div>";
                echo "<div class='result'>";
                echo "<h3>Setup Results:</h3>";
                echo "<pre>";
                foreach ($results as $result) {
                    echo $result . "\n";
                }
                echo "</pre>";
                echo "<h3>Next Steps:</h3>";
                echo "<p>🔗 <strong>Doctor Login:</strong> <a href='auth/doctor_login.php'>http://10.164.246.30/healthintel/auth/doctor_login.php</a></p>";
                echo "<p>👤 <strong>Demo Credentials:</strong></p>";
                echo "<ul>";
                echo "<li>Cardiologist: sarah.j@hospital.com / password</li>";
                echo "<li>Neurologist: michael.c@hospital.com / password</li>";
                echo "<li>Pediatrician: emily.d@hospital.com / password</li>";
                echo "</ul>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='status error'>❌ Error: " . $e->getMessage() . "</div>";
            }
        } else {
        ?>
        
        <div class="status info">
            <strong>📋 Ready to set up HealthIntel Doctor Module</strong><br>
            This setup will create the necessary database tables and sample doctor accounts.
        </div>
        
        <form method="POST">
            <input type="hidden" name="setup" value="1">
            <button type="submit" class="btn">🚀 Setup Database Now</button>
        </form>
        
        <div class="result">
            <h3>What will be created:</h3>
            <ul>
                <li>✅ doctor_login table - Doctor authentication</li>
                <li>✅ doctor_notes table - Medical notes and prescriptions</li>
                <li>✅ patient_access table - Patient assignments</li>
                <li>✅ alerts table - Medical alerts</li>
                <li>✅ Sample doctor accounts for testing</li>
            </ul>
        </div>
        
        <?php } ?>
    </div>
</body>
</html>
