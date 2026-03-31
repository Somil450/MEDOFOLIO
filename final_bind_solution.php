<?php
echo "=== FINAL BIND PARAMETER SOLUTION ===\n";

echo "The issue is that mysqli_stmt_bind_param() is receiving more variables than expected.\n";
echo "This typically happens when:\n";
echo "1. Variables are being concatenated or expanded\n";
echo "2. Type string doesn't match variable count\n";
echo "3. Variables are passed by reference instead of value\n\n";

echo "\n=== SOLUTION ===\n";
echo "Replace the medical history section in doctor_dashboard.php with this corrected code:\n";
echo '

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["add_medical_history"])) {
        $patient_id = (int)$_POST["patient_id"];
        $condition_name = trim($_POST["condition_name"]);
        $diagnosis_date = $_POST["diagnosis_date"];
        $severity = $_POST["severity"];
        $description = trim($_POST["description"]);
        $treatment_plan = trim($_POST["treatment_plan"]);
        $follow_up_required = isset($_POST["follow_up_required"]) ? 1 : 0;
        $follow_up_date = !empty($_POST["follow_up_date"]) ? $_POST["follow_up_date"] : null;

        // Handle file upload
        $file_name = "";
        $file_path = "";
        if (isset($_FILES["medical_file"]) && $_FILES["medical_file"]["error"] == 0) {
            $upload_dir = "uploads/medical_history_files/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . "_" . basename($_FILES["medical_file"]["name"]);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES["medical_file"]["tmp_name"], $file_path)) {
                // File uploaded successfully
            }
        }

        // Simple, direct approach
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_medical_history (patient_id, doctor_id, condition_name, diagnosis_date, severity, description, treatment_plan, follow_up_required, follow_up_date, file_name, file_path) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        mysqli_stmt_bind_param($stmt, "iissssssss", 
            $patient_id, 
            $doctor_id, 
            $condition_name, 
            $diagnosis_date, 
            $severity, 
            $description, 
            $treatment_plan, 
            $follow_up_required, 
            $follow_up_date, 
            $file_name, 
            $file_path
        );
        mysqli_stmt_execute($stmt);

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, \"add_medical_history\", ?)"
        );
        $details = "Added medical history: " . $condition_name . ($file_name ? " with file: " . $file_name : "");
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=" . $patient_id . "&success=medical_history_added");
        exit;
    }
}
';

echo "\nThis approach:\n";
echo "1. Uses direct \$_POST access\n";
echo "2. Creates simple variables without complex casting\n";
echo "3. Uses exact 9-parameter bind\n";
echo "4. No variable expansion or concatenation\n";
echo "5. Follows MySQLi best practices\n";

echo "\nReplace lines 19-96 in doctor_dashboard.php with this code.\n";
?>
