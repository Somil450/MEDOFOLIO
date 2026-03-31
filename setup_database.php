<?php
include "db.php";

echo "Setting up HealthIntel Doctor Module Database...\n";

try {
    // Read and execute the SQL setup file
    $sql = file_get_contents('database_setup.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if (!$conn->query($statement)) {
                echo "Error executing statement: " . $statement . "\n";
                echo "MySQL Error: " . $conn->error . "\n";
            } else {
                echo "✓ Successfully executed: " . substr($statement, 0, 50) . "...\n";
            }
        }
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "You can now access the doctor system at:\n";
    echo "http://10.70.79.30/healthintel/auth/doctor_login.php\n";
    echo "\nDemo accounts:\n";
    echo "- Cardiologist: sarah.j@hospital.com / password\n";
    echo "- Neurologist: michael.c@hospital.com / password\n";
    echo "- Pediatrician: emily.d@hospital.com / password\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
