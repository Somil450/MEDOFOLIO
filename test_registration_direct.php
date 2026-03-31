<?php
// Direct test of registration functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 Testing Registration Directly...\n\n";

try {
    include "db.php";
    
    if (!$conn) {
        die("❌ Database connection failed");
    }
    
    echo "✅ Database connected\n";
    
    // Test registration with sample data
    $_POST['register'] = 'test';
    $_POST['name'] = 'Dr. Test Doctor';
    $_POST['email'] = 'test.doctor@example.com';
    $_POST['password'] = 'testpass123';
    $_POST['confirm_password'] = 'testpass123';
    $_POST['specialization'] = 'General Physician';
    $_POST['hospital_name'] = 'Test Hospital';
    $_POST['license_number'] = 'TEST-123';
    $_POST['phone'] = '+1-555-123-4567';
    
    echo "📝 Simulating registration with:\n";
    echo "- Name: Dr. Test Doctor\n";
    echo "- Email: test.doctor@example.com\n";
    echo "- Specialization: General Physician\n";
    echo "- Hospital: Test Hospital\n";
    echo "- License: TEST-123\n";
    echo "- Phone: +1-555-123-4567\n\n";
    
    // Capture the registration logic output
    ob_start();
    include "auth/doctor_register.php";
    $output = ob_get_clean();
    
    echo "📄 Registration Output:\n";
    echo $output;
    
    if (strpos($output, 'Registration successful') !== false) {
        echo "\n✅ REGISTRATION WORKING! No errors detected.\n";
    } else {
        echo "\n❌ REGISTRATION FAILED! Check for errors above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
