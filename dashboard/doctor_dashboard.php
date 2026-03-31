<?php
session_start();

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    header("Location: ../auth/doctor_login.php");
    exit;
}

include "../db.php";

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];
$doctor_specialization = $_SESSION['doctor_specialization'];
$doctor_hospital = $_SESSION['doctor_hospital'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_medical_history'])) {
        $patient_id = (int)$_POST['patient_id'];
        $condition_name = trim($_POST['condition_name']);
        $diagnosis_date = $_POST['diagnosis_date'];
        
        $severity = $_POST['severity'];
        $description = trim($_POST['description']);
        $treatment_plan = trim($_POST['treatment_plan']);
        $follow_up_required = isset($_POST['follow_up_required']) ? 1 : 0;
        $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;

        // Debug: Log what we received
        error_log("Add Medical History POST: " . print_r($_POST, true));

        // Handle file upload
        $file_name = '';
        $file_path = '';
        if (isset($_FILES['medical_file']) && $_FILES['medical_file']['error'] == 0) {
            $upload_dir = 'uploads/medical_history_files/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['medical_file']['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['medical_file']['tmp_name'], $file_path)) {
                // File uploaded successfully
            }
        }

        // Create proper variables with explicit types
        $patient_id_param = (int)$patient_id;
        $doctor_id_param = (int)$doctor_id;
        $condition_name_param = $condition_name;
        $diagnosis_date_param = $diagnosis_date;
        $severity_param = $severity;
        $description_param = $description;
        $treatment_plan_param = $treatment_plan;
        $follow_up_param = (int)$follow_up_required;
        $follow_up_date_param = $follow_up_date;
        $file_name_param = $file_name;
        $file_path_param = $file_path;

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_medical_history (patient_id, doctor_id, condition_name, diagnosis_date, severity, description, treatment_plan, follow_up_required, follow_up_date, file_name, file_path) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        mysqli_stmt_bind_param($stmt, "iissssssiss", 
            $patient_id_param, 
            $doctor_id_param, 
            $condition_name_param, 
            $diagnosis_date_param, 
            $severity_param, 
            $description_param, 
            $treatment_plan_param, 
            $follow_up_param, 
            $follow_up_date_param, 
            $file_name_param, 
            $file_path_param
        );
        mysqli_stmt_execute($stmt);

        // Log activity with proper variables
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'add_medical_history', ?)"
        );
        $details_param = "Added medical history: $condition_name_param" . ($file_name_param ? " with file: $file_name_param" : "");
        $log_doctor_id_param = (int)$doctor_id;
        $log_patient_id_param = (int)$patient_id;
        mysqli_stmt_bind_param($log_stmt, "iis", $log_doctor_id_param, $log_patient_id_param, $details_param);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=medical_history_added");
        exit;
    }
    
    if (isset($_POST['add_medication'])) {
        $patient_id = (int)$_POST['patient_id'];
        $medication_name = trim($_POST['medication_name']);
        $dosage = trim($_POST['dosage']);
        $frequency = trim($_POST['frequency']);
        $route = $_POST['route'];
        $start_date = $_POST['start_date'];
        $purpose = trim($_POST['purpose']);
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $pharmacy_id = (int)$_POST['pharmacy_id'];
        $quantity = (int)$_POST['quantity'];
        $send_to_pharmacy = isset($_POST['send_to_pharmacy']) ? 1 : 0;
        $generate_bill = isset($_POST['generate_bill']) ? 1 : 0;

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO medications (patient_id, doctor_id, medication_name, dosage, frequency, route, start_date, end_date, purpose, prescribed_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iisssssssi", $patient_id, $doctor_id, $medication_name, $dosage, $frequency, $route, $start_date, $end_date, $purpose, $doctor_id);
        mysqli_stmt_execute($stmt);

        // Send to pharmacy if requested
        $order_id = null;
        if ($send_to_pharmacy && $pharmacy_id > 0) {
            // Get medication price from pharmacy inventory
            $price_stmt = mysqli_prepare($conn, "SELECT unit_price FROM medication_inventory WHERE pharmacy_id = ? AND medication_name = ?");
            mysqli_stmt_bind_param($price_stmt, "is", $pharmacy_id, $medication_name);
            mysqli_stmt_execute($price_stmt);
            $price_result = mysqli_stmt_get_result($price_stmt);
            $unit_price = 0;
            
            if ($row = mysqli_fetch_assoc($price_result)) {
                $unit_price = $row['unit_price'];
            }
            
            $total_price = $unit_price * $quantity;
            
            // Create pharmacy order
            $order_stmt = mysqli_prepare($conn, 
                "INSERT INTO pharmacy_orders (patient_id, doctor_id, pharmacy_id, medication_name, dosage, quantity, unit_price, total_price, prescription_notes, order_status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $prescription_notes = "Prescribed by Dr. " . $_SESSION['doctor_name'] . ". Purpose: " . $purpose;
            mysqli_stmt_bind_param($order_stmt, "iiisiddss", $patient_id, $doctor_id, $pharmacy_id, $medication_name, $dosage, $quantity, $unit_price, $total_price, $prescription_notes);
            mysqli_stmt_execute($order_stmt);
            $order_id = mysqli_insert_id($conn);
        }

        // Generate bill if requested
        if ($generate_bill) {
            $bill_amount = 0;
            $bill_description = "Medication: " . $medication_name . " - " . $dosage;
            
            if ($order_id) {
                // Use pharmacy order total
                $bill_stmt = mysqli_prepare($conn, "SELECT total_price FROM pharmacy_orders WHERE order_id = ?");
                mysqli_stmt_bind_param($bill_stmt, "i", $order_id);
                mysqli_stmt_execute($bill_stmt);
                $bill_result = mysqli_stmt_get_result($bill_stmt);
                if ($row = mysqli_fetch_assoc($bill_result)) {
                    $bill_amount = $row['total_price'];
                }
                $bill_description .= " (Pharmacy Order #" . $order_id . ")";
            } else {
                // Use consultation fee
                $bill_amount = 50.00; // Default consultation fee
                $bill_description .= " (Consultation Fee)";
            }
            
            // Create medical bill
            $bill_stmt = mysqli_prepare($conn, 
                "INSERT INTO medical_bills (patient_id, doctor_id, order_id, bill_type, description, amount, total_amount, due_date) 
                 VALUES (?, ?, ?, 'medication', ?, ?, ?, DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY))"
            );
            mysqli_stmt_bind_param($bill_stmt, "iiisdd", $patient_id, $doctor_id, $order_id, $bill_description, $bill_amount, $bill_amount);
            mysqli_stmt_execute($bill_stmt);
        }

        // Log activity
        $log_details = "Prescribed medication: $medication_name";
        if ($send_to_pharmacy) {
            $log_details .= " (Sent to pharmacy)";
        }
        if ($generate_bill) {
            $log_details .= " (Bill generated)";
        }
        
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'add_medication', ?)"
        );
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $log_details);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=medication_added");
        exit;
    }
    
    if (isset($_POST['edit_medication'])) {
        $medication_id = (int)$_POST['medication_id'];
        $patient_id = (int)$_POST['patient_id'];
        $medication_name = trim($_POST['medication_name']);
        $dosage = trim($_POST['dosage']);
        $frequency = trim($_POST['frequency']);
        $route = $_POST['route'];
        $start_date = $_POST['start_date'];
        $purpose = trim($_POST['purpose']);
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        $stmt = mysqli_prepare($conn, 
            "UPDATE medications SET medication_name = ?, dosage = ?, frequency = ?, route = ?, start_date = ?, end_date = ?, purpose = ? 
             WHERE id = ? AND patient_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "sssssssi", $medication_name, $dosage, $frequency, $route, $start_date, $end_date, $purpose, $medication_id, $patient_id);
        mysqli_stmt_execute($stmt);

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'edit_medication', ?)"
        );
        $details = "Updated medication: $medication_name";
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=medication_updated");
        exit;
    }
    
    if (isset($_POST['delete_medication'])) {
        $medication_id = (int)$_POST['medication_id'];
        $patient_id = (int)$_POST['patient_id'];

        // Get medication details for logging
        $med_stmt = mysqli_prepare($conn, "SELECT medication_name FROM medications WHERE id = ? AND patient_id = ?");
        mysqli_stmt_bind_param($med_stmt, "ii", $medication_id, $patient_id);
        mysqli_stmt_execute($med_stmt);
        $med_result = mysqli_stmt_get_result($med_stmt);
        $medication_name = "Unknown";
        if ($row = mysqli_fetch_assoc($med_result)) {
            $medication_name = $row['medication_name'];
        }

        // Delete the medication
        $stmt = mysqli_prepare($conn, "DELETE FROM medications WHERE id = ? AND patient_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $medication_id, $patient_id);
        mysqli_stmt_execute($stmt);

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'delete_medication', ?)"
        );
        $details = "Deleted medication: $medication_name";
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=medication_deleted");
        exit;
    }
    
    if (isset($_POST['add_vitals'])) {
        $patient_id = (int)$_POST['patient_id'];
        $bp_systolic = !empty($_POST['bp_systolic']) ? (int)$_POST['bp_systolic'] : null;
        $bp_diastolic = !empty($_POST['bp_diastolic']) ? (int)$_POST['bp_diastolic'] : null;
        $heart_rate = !empty($_POST['heart_rate']) ? (int)$_POST['heart_rate'] : null;
        $temperature = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
        $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
        $oxygen_saturation = !empty($_POST['oxygen_saturation']) ? (int)$_POST['oxygen_saturation'] : null;
        $blood_sugar = !empty($_POST['blood_sugar']) ? (float)$_POST['blood_sugar'] : null;
        $notes = trim($_POST['vitals_notes']);

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO vitals (patient_id, doctor_id, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, temperature, weight, height, oxygen_saturation, blood_sugar, notes, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iiiiiddiiisi", $patient_id, $doctor_id, $bp_systolic, $bp_diastolic, $heart_rate, $temperature, $weight, $height, $oxygen_saturation, $blood_sugar, $notes, $doctor_id);
        mysqli_stmt_execute($stmt);

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'add_vitals', 'Vital signs recorded')"
        );
        mysqli_stmt_bind_param($log_stmt, "ii", $doctor_id, $patient_id);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=vitals_added");
        exit;
    }
    
    if (isset($_POST['add_allergy'])) {
        $patient_id = (int)$_POST['patient_id'];
        $allergen = trim($_POST['allergen']);
        $allergy_type = $_POST['allergy_type'];
        $severity = $_POST['severity'];
        $reaction = trim($_POST['reaction']);
        $diagnosed_date = $_POST['diagnosed_date'];
        $notes = trim($_POST['allergy_notes']);

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO allergies (patient_id, doctor_id, allergen, allergy_type, severity, reaction, diagnosed_date, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iissssss", $patient_id, $doctor_id, $allergen, $allergy_type, $severity, $reaction, $diagnosed_date, $notes);
        mysqli_stmt_execute($stmt);

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'add_allergy', ?)"
        );
        $details = "Added allergy: $allergen";
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=allergy_added");
        exit;
    }
    
    if (isset($_POST['add_doctor_note'])) {
        $patient_id = (int)$_POST['patient_id'];
        $note_content = trim($_POST['note_content']);
        $note_type = $_POST['note_type'] ?? 'clinical';
        
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_notes (patient_id, doctor_id, note_content, note_type) 
             VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iiss", $patient_id, $doctor_id, $note_content, $note_type);
        mysqli_stmt_execute($stmt);

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'add_doctor_note', ?)"
        );
        $details = "Added doctor note: " . substr($note_content, 0, 50) . (strlen($note_content) > 50 ? '...' : '');
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=doctor_note_added");
        exit;
    }
    
    if (isset($_POST['edit_medical_history'])) {
        $history_id = (int)$_POST['history_id'];
        $source_table = $_POST['source_table'];
        $severity = $_POST['severity'];
        $status = $_POST['status'];
        $description = trim($_POST['description']);
        $treatment_plan = trim($_POST['treatment_plan']);
        $patient_id = (int)$_POST['patient_id'];

        if ($source_table == 'patient_disease_history') {
            // Update existing medofolio table
            $treating_hospital = isset($_POST['treating_hospital']) ? trim($_POST['treating_hospital']) : '';
            
            $stmt = mysqli_prepare($conn, 
                "UPDATE patient_disease_history SET severity_level = ?, status = ?, notes = ?, treating_hospital = ? WHERE history_id = ?"
            );
            mysqli_stmt_bind_param($stmt, "ssssi", $severity, $status, $description, $treating_hospital, $history_id);
            mysqli_stmt_execute($stmt);
        } else {
            // Update new doctor_medical_history table
            $stmt = mysqli_prepare($conn, 
                "UPDATE doctor_medical_history SET severity = ?, status = ?, description = ?, treatment_plan = ?, doctor_id = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "ssssii", $severity, $status, $description, $treatment_plan, $doctor_id, $history_id);
            mysqli_stmt_execute($stmt);
        }

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'edit_medical_history', ?)"
        );
        $details = "Edited medical history in $source_table";
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=medical_history_updated");
        exit;
    }
    
    if (isset($_POST['delete_medical_history'])) {
        $history_id = (int)$_POST['history_id'];
        $source_table = $_POST['source_table'];
        $patient_id = (int)$_POST['patient_id'];

        if ($source_table == 'patient_disease_history') {
            // Don't allow deletion of medofolio data
            header("Location: ?patient_id=$patient_id&error=cannot_delete_medofolio");
            exit;
        } else {
            // Delete from new doctor_medical_history table
            $stmt = mysqli_prepare($conn, "DELETE FROM doctor_medical_history WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $history_id);
            mysqli_stmt_execute($stmt);
        }

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'delete_medical_history', ?)"
        );
        $details = 'Deleted medical history record';
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=medical_history_deleted");
        exit;
    }
    
    if (isset($_POST['add_lab_result'])) {
        $patient_id = (int)$_POST['patient_id'];
        $test_name = trim($_POST['test_name']);
        $test_type = trim($_POST['test_type']);
        $test_date = $_POST['test_date'];
        $result_value = trim($_POST['result_value']);
        $normal_range = trim($_POST['normal_range']);
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);
        $reported_by = trim($_POST['reported_by']);
        
        // Handle file upload
        $file_path = '';
        if (isset($_FILES['lab_file']) && $_FILES['lab_file']['error'] == 0) {
            $upload_dir = 'uploads/doctor_lab_reports/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['lab_file']['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['lab_file']['tmp_name'], $file_path)) {
                // Store file path in notes or separate field
                $notes .= "\n\nFile uploaded: " . $file_name;
            }
        }
        
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO lab_results (patient_id, doctor_id, test_name, test_type, result_value, normal_range, status, notes, test_date, reported_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "isssssssss", $patient_id, $doctor_id, $test_name, $test_type, $result_value, $normal_range, $status, $notes, $test_date, $reported_by);
        mysqli_stmt_execute($stmt);

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'add_lab_result', ?)"
        );
        $details = "Added lab result: $test_name";
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);

        header("Location: ?patient_id=$patient_id&success=lab_result_added");
        exit;
    }
    
    if (isset($_POST['add_note'])) {
        $patient_id = (int)$_POST['patient_id'];
        $diagnosis = trim($_POST['diagnosis']);
        $prescription = trim($_POST['prescription']);
        $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
        $severity = $_POST['severity'];

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_notes (patient_id, doctor_id, diagnosis, prescription, follow_up_date, severity) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iissss", $patient_id, $doctor_id, $diagnosis, $prescription, $follow_up_date, $severity);
        mysqli_stmt_execute($stmt);

        // Log activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'add_note', ?)"
        );
        $details = "Added note: " . substr($diagnosis, 0, 50) . "...";
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);

        header("Location: doctor_dashboard.php?patient_id=" . $patient_id . "&success=note_added");
        exit;
    }

    if (isset($_POST['assign_patient'])) {
        $patient_id = (int)$_POST['patient_id'];
        
        $stmt = mysqli_prepare($conn, 
            "INSERT IGNORE INTO patient_access (patient_id, doctor_id, assigned_by) VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iii", $patient_id, $doctor_id, $doctor_id);
        mysqli_stmt_execute($stmt);

        header("Location: doctor_dashboard.php?patient_id=" . $patient_id . "&success=assigned");
        exit;
    }

    if (isset($_POST['create_alert'])) {
        $patient_id = (int)$_POST['patient_id'];
        $message = trim($_POST['alert_message']);
        $severity = $_POST['alert_severity'];
        $alert_type = $_POST['alert_type'];

        $stmt = mysqli_prepare($conn, 
            "INSERT INTO alerts (patient_id, doctor_id, message, severity, alert_type) 
             VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iisss", $patient_id, $doctor_id, $message, $severity, $alert_type);
        mysqli_stmt_execute($stmt);

        header("Location: doctor_dashboard.php?patient_id=" . $patient_id . "&success=alert_created");
        exit;
    }
}

// Get dashboard statistics
$total_patients = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(DISTINCT patient_id) as count FROM patient_access WHERE doctor_id = $doctor_id AND is_active = 1"
))['count'] ?? 0;

$recent_notes = mysqli_query($conn, 
    "SELECT dn.*, p.name as patient_name, p.dob, p.gender
     FROM doctor_notes dn 
     JOIN patient p ON dn.patient_id = p.patient_id 
     WHERE dn.doctor_id = $doctor_id 
     ORDER BY dn.created_at DESC LIMIT 5"
);

$alerts_count = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM alerts WHERE doctor_id = $doctor_id AND is_read = 0"
))['count'] ?? 0;

$recent_alerts = mysqli_query($conn, 
    "SELECT a.*, p.name as patient_name
     FROM alerts a 
     JOIN patient p ON a.patient_id = p.patient_id
     WHERE a.doctor_id = $doctor_id 
     ORDER BY a.created_at DESC LIMIT 5"
);

// Get all patients for this doctor (including real medofolio patients) with pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $patients_per_page = 8; // Show only 8 patients per page
        $offset = ($page - 1) * $patients_per_page;
        
        $total_patients_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM patient");
        $total_patients = mysqli_fetch_assoc($total_patients_result)['total'];
        $total_pages = ceil($total_patients / $patients_per_page);
        
        $all_patients = mysqli_query($conn, 
            "SELECT p.patient_id, p.name, p.dob, p.gender, p.region,
                    COALESCE((SELECT COUNT(*) FROM doctor_notes WHERE patient_id = p.patient_id AND doctor_id = $doctor_id), 0) as notes_count,
                    COALESCE((SELECT COUNT(*) FROM alerts WHERE patient_id = p.patient_id AND doctor_id = $doctor_id AND is_read = 0), 0) as unread_alerts,
                    CASE 
                        WHEN pa.patient_id IS NOT NULL THEN 'assigned'
                        ELSE 'available'
                    END as assignment_status
             FROM patient p 
             LEFT JOIN patient_access pa ON p.patient_id = pa.patient_id AND pa.doctor_id = $doctor_id
             ORDER BY p.name ASC 
             LIMIT $patients_per_page OFFSET $offset"
        );

// Handle patient search
$searched_patient = null;
if (isset($_GET['search']) || isset($_GET['patient_id'])) {
    $patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : (int)$_GET['search'];
    
    // Get patient details (works for any patient in the system)
    $patient_query = mysqli_query($conn, 
        "SELECT p.patient_id, p.name, p.dob, p.gender, p.region,
                COALESCE((SELECT COUNT(*) FROM doctor_notes WHERE patient_id = p.patient_id AND doctor_id = $doctor_id), 0) as notes_count,
                COALESCE((SELECT COUNT(*) FROM alerts WHERE patient_id = p.patient_id AND doctor_id = $doctor_id AND is_read = 0), 0) as unread_alerts
         FROM patient p 
         WHERE p.patient_id = $patient_id"
    );
    $searched_patient = mysqli_fetch_assoc($patient_query);

    if ($searched_patient) {
        // Automatically assign patient to doctor if not already assigned
        $assign_check = mysqli_query($conn, 
            "SELECT COUNT(*) as count FROM patient_access WHERE patient_id = $patient_id AND doctor_id = $doctor_id"
        );
        $is_assigned = mysqli_fetch_assoc($assign_check)['count'];
        
        if ($is_assigned == 0) {
            // Assign patient to this doctor
            mysqli_query($conn, 
                "INSERT INTO patient_access (patient_id, doctor_id, access_level, is_active) 
                 VALUES ($patient_id, $doctor_id, 'full', 1)"
            );
        }
    }

        // Get patient medical history (from existing medofolio tables + new entries)
        $medical_history = mysqli_query($conn, 
            "SELECT 
                pdh.history_id as id,
                pdh.patient_id,
                pdh.disease_id,
                pdh.detected_date as diagnosis_date,
                pdh.severity_level as severity,
                pdh.status,
                pdh.treating_hospital,
                pdh.notes as description,
                NULL as treatment_plan,
                NULL as file_name,
                NULL as file_path,
                'patient_disease_history' as source_table,
                'Medofolio Patient' as source,
                NULL as doctor_id,
                NULL as doctor_name,
                NULL as specialization,
                NULL as follow_up_required,
                NULL as follow_up_date
             FROM patient_disease_history pdh 
             WHERE pdh.patient_id = $patient_id
             
             UNION ALL
             
             SELECT 
                dmh.id,
                dmh.patient_id,
                NULL as disease_id,
                dmh.diagnosis_date,
                dmh.severity,
                dmh.status,
                NULL as treating_hospital,
                dmh.description,
                dmh.treatment_plan,
                dmh.file_name,
                dmh.file_path,
                'doctor_medical_history' as source_table,
                CASE 
                    WHEN dmh.doctor_id = 1 THEN 'Doctor Added'
                    ELSE 'Patient Uploaded'
                END as source,
                dmh.doctor_id,
                dl.name as doctor_name,
                dl.specialization,
                dmh.follow_up_required,
                dmh.follow_up_date
             FROM doctor_medical_history dmh 
             LEFT JOIN doctor_login dl ON dmh.doctor_id = dl.doctor_id 
             WHERE dmh.patient_id = $patient_id
             
             ORDER BY diagnosis_date DESC"
        );

        // Get patient medications (uploaded by patient or prescribed by doctor)
        $medications = mysqli_query($conn, 
            "SELECT m.*, dl.name as prescribing_doctor,
                    CASE 
                        WHEN m.prescribed_by = 1 THEN 'Doctor Prescribed'
                        ELSE 'Patient Added'
                    END as source
             FROM medications m 
             LEFT JOIN doctor_login dl ON m.prescribed_by = dl.doctor_id 
             WHERE m.patient_id = $patient_id AND m.status = 'active'
             ORDER BY m.start_date DESC"
        );

        // Get available pharmacies
        $pharmacies = mysqli_query($conn, 
            "SELECT pharmacy_id, pharmacy_name, address, phone, operating_hours 
             FROM pharmacy 
             WHERE status = 'active' 
             ORDER BY pharmacy_name"
        );

        // Get patient bills
        $bills = mysqli_query($conn, 
            "SELECT mb.*, po.order_id, po.medication_name, po.order_status,
                    po.total_price as order_total, dl.name as doctor_name,
                    CASE 
                        WHEN mb.payment_method IS NOT NULL AND mb.payment_method != '' THEN 'Paid'
                        WHEN mb.due_date < CURDATE() THEN 'Overdue'
                        WHEN mb.due_date = CURDATE() THEN 'Due Today'
                        ELSE 'Pending'
                    END as payment_status
             FROM medical_bills mb
             LEFT JOIN pharmacy_orders po ON mb.order_id = po.order_id
             LEFT JOIN doctor_login dl ON mb.doctor_id = dl.doctor_id
             WHERE mb.patient_id = $patient_id 
             ORDER BY mb.billing_date DESC"
        );

        // Get patient allergies
        $allergies = mysqli_query($conn, 
            "SELECT a.*, dl.name as doctor_name,
                    CASE 
                        WHEN a.doctor_id = 1 THEN 'Doctor Recorded'
                        ELSE 'Patient Recorded'
                    END as source
             FROM allergies a 
             LEFT JOIN doctor_login dl ON a.doctor_id = dl.doctor_id 
             WHERE a.patient_id = $patient_id AND a.status = 'active'
             ORDER BY a.severity DESC"
        );

        // Get lab results (from existing medofolio medical_reports + new entries)
        $lab_results = mysqli_query($conn, 
            "SELECT 
                mr.report_id as id,
                mr.patient_id,
                mr.disease_name as test_name,
                'Medical Report' as test_type,
                mr.file_name,
                mr.file_path,
                mr.uploaded_at as test_date,
                NULL as result_value,
                NULL as normal_range,
                'uploaded' as status,
                NULL as notes,
                'medical_reports' as source_table,
                'Medofolio Patient' as source,
                NULL as doctor_id,
                NULL as doctor_name,
                NULL as specialization,
                NULL as reported_by
             FROM medical_reports mr 
             WHERE mr.patient_id = $patient_id
             
             UNION ALL
             
             SELECT 
                lr.id,
                lr.patient_id,
                lr.test_name,
                lr.test_type,
                NULL as file_name,
                NULL as file_path,
                lr.test_date,
                lr.result_value,
                lr.normal_range,
                lr.status,
                lr.notes,
                'lab_results' as source_table,
                CASE 
                    WHEN lr.doctor_id = 1 THEN 'Doctor Added'
                    ELSE 'Patient Uploaded'
                END as source,
                lr.doctor_id,
                dl.name as doctor_name,
                dl.specialization,
                lr.reported_by
             FROM lab_results lr 
             LEFT JOIN doctor_login dl ON lr.doctor_id = dl.doctor_id 
             WHERE lr.patient_id = $patient_id
             
             ORDER BY test_date DESC"
        );

        // Get doctor notes
        $doctor_notes = mysqli_query($conn, 
            "SELECT dn.*, dl.name as doctor_name, dl.specialization 
             FROM doctor_notes dn 
             JOIN doctor_login dl ON dn.doctor_id = dl.doctor_id 
             WHERE dn.patient_id = $patient_id 
             ORDER BY dn.created_at DESC"
        );

        // Get patient alerts
        $patient_alerts = mysqli_query($conn, 
            "SELECT * FROM alerts WHERE patient_id = $patient_id ORDER BY created_at DESC"
        );
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - HealthIntel</title>
    <link rel="stylesheet" href="../assets/premium-medical.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: #F8FAFF;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .doctor-info h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .doctor-info p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }

        .dashboard-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #4A90E2;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 15px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #4A90E2;
            margin-bottom: 5px;
        }

        .stat-card .stat-label {
            color: #666;
            font-size: 13px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #F0F4F8;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .search-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E1E8ED;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
        }

        .btn-secondary {
            background: #F0F4F8;
            color: #333;
            border: 2px solid #E1E8ED;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #E1E8ED;
        }

        .btn-edit-profile {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .btn-edit-profile:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .edit-form-section {
            background: #F8FAFF;
            border: 2px solid #E1E8ED;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
        }

        .patient-edit-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .form-actions .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .form-actions .btn-secondary {
            background: #F0F4F8;
            color: #666;
            border: 2px solid #E1E8ED;
        }

        .medical-form {
            background: #F8FAFF;
            border: 1px solid #E1E8ED;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }

        .medical-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .medical-form .form-group {
            margin-bottom: 15px;
        }

        .medical-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .medical-form input,
        .medical-form select,
        .medical-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .medical-form textarea {
            resize: vertical;
        }

        .medical-form .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .medical-history-item {
            background: #fff;
            border: 1px solid #e1e8ed;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .medical-history-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .medical-history-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .medical-history-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .medical-history-meta {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .medical-history-date {
            color: #7f8c8d;
            font-size: 14px;
        }

        .medical-history-content {
            margin-bottom: 15px;
        }

        .medical-history-field {
            margin-bottom: 10px;
        }

        .medical-history-label {
            font-weight: 600;
            color: #34495e;
            margin-bottom: 3px;
        }

        .medical-history-value {
            color: #555;
            line-height: 1.5;
        }

        .medical-history-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .btn-medical-edit, .btn-medical-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-medical-edit {
            background: #3498db;
            color: white;
        }

        .btn-medical-edit:hover {
            background: #2980b9;
        }

        .btn-medical-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-medical-delete:hover {
            background: #c0392b;
        }

        .source-medofolio {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .source-doctor {
            background: linear-gradient(135deg, #4caf50, #388e3c);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .source-patient {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .severity-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .severity-low {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .severity-medium {
            background: #fff3e0;
            color: #f57c00;
        }

        .severity-high {
            background: #ffebee;
            color: #c62828;
        }

        .severity-critical {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-recovered {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-critical {
            background: #ffebee;
            color: #c62828;
        }

        .status-resolved {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .pagination-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e1e8ed;
        }

        .pagination-info {
            color: #666;
            font-weight: 500;
        }

        .pagination-links {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pagination-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination-link:hover {
            background: #4A90E2;
            color: white;
            border-color: #4A90E2;
        }

        .pagination-link.active {
            background: #4A90E2;
            color: white;
            border-color: #4A90E2;
        }

        .file-link {
            color: #4A90E2;
            text-decoration: none;
            padding: 4px 8px;
            border: 1px solid #4A90E2;
            border-radius: 4px;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .file-link:hover {
            background: #4A90E2;
            color: white;
        }

        .vital-values {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .vital-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #F0F4F8;
            border-radius: 6px;
            border-left: 3px solid #4A90E2;
        }

        .vital-label {
            font-weight: 500;
            color: #666;
        }

        .vital-value {
            font-weight: 600;
            color: #333;
        }

        .vital-notes {
            margin-top: 10px;
            padding: 10px;
            background: #FFF9E6;
            border-radius: 6px;
            border-left: 3px solid #FF9800;
        }

        .vital-doctor {
            margin-top: 8px;
            text-align: right;
            color: #666;
            font-size: 12px;
        }

        .status-normal {
            color: #4CAF50;
            font-weight: 600;
        }

        .status-abnormal {
            color: #FF9800;
            font-weight: 600;
        }

        .status-critical {
            color: #F44336;
            font-weight: 600;
        }

        .source-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-left: 10px;
        }

        .doctor-source {
            background: #E8F5E8;
            color: #2D7A2D;
        }

        .patient-source {
            background: #E3F2FD;
            color: #1565C0;
        }

        .medofolio-source {
            background: #FFF3E0;
            color: #E65100;
            font-weight: 600;
        }

        .edit-actions {
            margin-top: 10px;
            display: flex;
            gap: 8px;
        }

        .btn-edit-small, .btn-delete-small {
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-edit-small {
            background: #4A90E2;
            color: white;
        }

        .btn-edit-small:hover {
            background: #357ABD;
        }

        .btn-delete-small {
            background: #F44336;
            color: white;
        }

        .btn-delete-small:hover {
            background: #D32F2F;
        }

        .patients-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .patients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .patients-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .patients-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        .patient-card {
            background: #F8FAFF;
            border: 2px solid #E1E8ED;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .patient-card:hover {
            border-color: #4A90E2;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 144, 226, 0.15);
        }

        .patient-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .patient-card-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .patient-meta-badges {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .patient-id {
            background: #4A90E2;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }

        .assignment-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .assignment-badge.assigned {
            background: #E8F5E8;
            color: #2D7A2D;
            border: 1px solid #C3E6C3;
        }

        .assignment-badge.available {
            background: #FFF4E6;
            color: #D97600;
            border: 1px solid #FFD4A3;
        }

        .patient-card.assigned {
            border-left: 4px solid #4CAF50;
        }

        .patient-card.available {
            border-left: 4px solid #FF9800;
        }

        .patient-card-body {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .patient-info-row {
            display: flex;
            gap: 15px;
            font-size: 14px;
            color: #666;
        }

        .patient-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .patient-stats .stat {
            text-align: center;
            flex: 1;
        }

        .stat-number {
            display: block;
            font-size: 20px;
            font-weight: 600;
            color: #4A90E2;
        }

        .stat-label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        .patient-view {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #F0F4F8;
        }

        .patient-info h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 24px;
        }

        .patient-meta {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #F0F4F8;
            margin-bottom: 25px;
        }

        .tab {
            padding: 12px 20px;
            background: none;
            border: none;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab.active {
            color: #4A90E2;
            border-bottom-color: #4A90E2;
        }

        .tab:hover {
            color: #4A90E2;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .vital-card {
            background: #F8FAFF;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #4A90E2;
        }

        .vital-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .vital-title {
            font-weight: 600;
            color: #333;
        }

        .vital-time {
            color: #666;
            font-size: 12px;
        }

        .vital-values {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .vital-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .vital-label {
            color: #666;
            font-size: 13px;
        }

        .vital-value {
            font-weight: 600;
            color: #333;
        }

        .note-item {
            background: #F8FAFF;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #4A90E2;
        }

        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .note-doctor {
            font-weight: 600;
            color: #333;
        }

        .note-date {
            color: #666;
            font-size: 12px;
        }

        .note-content {
            margin-bottom: 10px;
        }

        .note-diagnosis {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }

        .note-prescription {
            color: #666;
            font-size: 14px;
        }

        .alert-item {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid;
        }

        .alert-item.high {
            background: #FEE;
            border-left-color: #F66;
        }

        .alert-item.medium {
            background: #FFF8E1;
            border-left-color: #FFA000;
        }

        .alert-item.low {
            background: #E8F5E8;
            border-left-color: #4CAF50;
        }

        .alert-item.critical {
            background: #FFEBEE;
            border-left-color: #F44336;
        }

        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .alert-patient {
            font-weight: 600;
            color: #333;
        }

        .alert-time {
            color: #666;
            font-size: 12px;
        }

        .alert-message {
            color: #333;
            font-size: 14px;
        }

        .success-message {
            background: #E8F5E8;
            color: #2E7D32;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #4CAF50;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #999;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .patient-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
        
        /* Medication Edit/Delete Styles */
        .note-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e1e8ed;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-small:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-primary.btn-small {
            background: linear-gradient(135deg, #4A90E2, #357ABD);
            color: white;
        }
        
        .btn-danger.btn-small {
            background: linear-gradient(135deg, #F44336, #D32F2F);
            color: white;
        }
        
        .btn-secondary.btn-small {
            background: linear-gradient(135deg, #6C757D, #5A6268);
            color: white;
        }
        
        .edit-form {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            border-left: 4px solid #4A90E2;
        }
        
        .edit-form .medical-form {
            margin: 0;
        }
        
        .edit-form .form-group {
            margin-bottom: 12px;
        }
        
        .edit-form .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        /* Medication Details Styling */
        .medication-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            min-width: 100px;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #e8f5e8;
            color: #2e7d2e;
        }
        
        .status-completed {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .status-discontinued {
            background: #ffebee;
            color: #c62828;
        }
        
        .status-on_hold {
            background: #fff3e0;
            color: #f57c00;
        }
        
        /* Payment Button Styling */
        .payment-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .payment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
        }
        
        .payment-btn.paid {
            background: linear-gradient(135deg, #9E9E9E, #757575);
            cursor: default;
        }
        
        .payment-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #4CAF50;
        }
        
        .payment-method {
            font-size: 12px;
            color: #666;
        }
        
        /* Payment Form Styling */
        .payment-form {
            margin-top: 15px;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }
        
        .payment-form h4 {
            margin: 0 0 15px 0;
            color: #2e7d2e;
            font-size: 16px;
        }
        
        .payment-form .form-group {
            margin-bottom: 15px;
        }
        
        .payment-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .payment-form select,
        .payment-form input,
        .payment-form textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .payment-form .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .form-help-text {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            padding: 8px 12px;
            background: #f0f8ff;
            border-left: 3px solid #4A90E2;
            border-radius: 4px;
        }
        
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
            color: #333;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        
        .pending-indicator {
            text-align: center;
            padding: 10px;
            background: rgba(255, 152, 0, 0.1);
            border-radius: 6px;
            border-left: 4px solid #ff9800;
        }
        
        .pending-indicator small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        
        .empty-state-actions {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #4A90E2;
        }
        
        .empty-state-actions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .empty-state-actions li {
            margin: 5px 0;
            color: #555;
        }
        
        .empty-state-actions em {
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="doctor-info">
                    <h1>Dr. <?php echo htmlspecialchars($doctor_name); ?></h1>
                    <p><?php echo htmlspecialchars($doctor_specialization); ?> • <?php echo htmlspecialchars($doctor_hospital); ?></p>
                </div>
                <div class="header-actions">
                    <a href="../auth/logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <?php
                    switch($_GET['success']) {
                        case 'note_added': echo '✓ Doctor note added successfully'; break;
                        case 'assigned': echo '✓ Patient assigned to your care'; break;
                        case 'alert_created': echo '✓ Alert created successfully'; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Patients</h3>
                    <div class="stat-value"><?php echo $total_patients; ?></div>
                    <div class="stat-label">Under your care</div>
                </div>
                <div class="stat-card">
                    <h3>Active Alerts</h3>
                    <div class="stat-value"><?php echo $alerts_count; ?></div>
                    <div class="stat-label">Require attention</div>
                </div>
                <div class="stat-card">
                    <h3>Recent Notes</h3>
                    <div class="stat-value"><?php echo mysqli_num_rows($recent_notes); ?></div>
                    <div class="stat-label">Last 7 days</div>
                </div>
                <div class="stat-card">
                    <h3>System Status</h3>
                    <div class="stat-value">●</div>
                    <div class="stat-label">All systems operational</div>
                </div>
            </div>

            <div class="patients-section">
                <div class="section-header">
                    <h2 class="section-title">All Patients (Medofolio Integration)</h2>
                    <div class="section-subtitle">
                        Showing <?php echo mysqli_num_rows($all_patients); ?> of <?php echo $total_patients; ?> patients • 
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?> • 
                        Click any patient to view/edit medical history
                    </div>
                </div>
                <?php if (mysqli_num_rows($all_patients) > 0): ?>
                    <div class="patients-grid">
                        <?php while ($patient = mysqli_fetch_assoc($all_patients)): ?>
                            <div class="patient-card <?php echo $patient['assignment_status'] == 'assigned' ? 'assigned' : 'available'; ?>" onclick="window.location.href='?patient_id=<?php echo $patient['patient_id']; ?>'">
                                <div class="patient-card-header">
                                    <h3><?php echo htmlspecialchars($patient['name']); ?></h3>
                                    <div class="patient-meta-badges">
                                        <span class="patient-id">ID: <?php echo $patient['patient_id']; ?></span>
                                        <span class="assignment-badge <?php echo $patient['assignment_status']; ?>">
                                            <?php echo $patient['assignment_status'] == 'assigned' ? '✓ Assigned' : '+ Available'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="patient-card-body">
                                    <div class="patient-info-row">
                                        <span>🎂 Age: <?php echo $patient['dob'] && $patient['dob'] !== '' ? (date('Y') - date('Y', strtotime($patient['dob'])) . ' years') : 'Not specified'; ?></span>
                                        <span>⚧ <?php echo $patient['gender'] && $patient['gender'] !== '' ? htmlspecialchars($patient['gender']) : 'Not specified'; ?></span>
                                    </div>
                                    <div class="patient-info-row">
                                        <span>🌍 <?php echo $patient['region'] && $patient['region'] !== '' ? htmlspecialchars($patient['region']) : 'Not specified'; ?></span>
                                    </div>
                                    <div class="patient-stats">
                                        <div class="stat">
                                            <span class="stat-number"><?php echo $patient['notes_count']; ?></span>
                                            <span class="stat-label">Notes</span>
                                        </div>
                                        <div class="stat">
                                            <span class="stat-number"><?php echo $patient['unread_alerts']; ?></span>
                                            <span class="stat-label">Alerts</span>
                                        </div>
                                        <div class="stat">
                                            <span class="stat-number"><?php echo $patient['assignment_status'] == 'assigned' ? '✓' : '+'; ?></span>
                                            <span class="stat-label">Status</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-controls">
                            <div class="pagination-info">
                                <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                            </div>
                            <div class="pagination-links">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="pagination-link">« Previous</a>
                                <?php endif; ?>
                                
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++): 
                                ?>
                                    <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="pagination-link">Next »</a>
                                <?php endif; ?>
                            
                    
                    </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Patients Found</h3>
                        <p>No patients are available in the system.</p>
                    </div>
                <?php endif; ?>
            
                    
                    </div>

            <div class="search-section">
                <div class="section-header">
                    <h2 class="section-title">Patient Search</h2>
                </div>
                <form method="GET" class="search-form">
                    <div class="form-group">
                        <label for="search">Patient ID</label>
                        <input type="number" id="search" name="search" placeholder="Enter patient ID" required>
                    </div>
                    <button type="submit" class="btn-primary">Search Patient</button>
                </form>
            </div>

            <?php if ($searched_patient): ?>
                <div class="patient-view">
                    <div class="patient-header">
                        <div class="patient-info">
                            <h2><?php echo htmlspecialchars($searched_patient['name']); ?></h2>
                            <div class="patient-meta">
                                <div class="meta-item">
                                    <span>🆔</span>
                                    <span>ID: <?php echo $searched_patient['patient_id']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span>🎂</span>
                                    <span>Age: <?php echo $searched_patient['dob'] && $searched_patient['dob'] !== '' ? (date('Y') - date('Y', strtotime($searched_patient['dob'])) . ' years') : 'Not specified'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span>⚧</span>
                                    <span><?php echo $searched_patient['gender'] && $searched_patient['gender'] !== '' ? htmlspecialchars($searched_patient['gender']) : 'Not specified'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span>🌍</span>
                                    <span><?php echo $searched_patient['region'] && $searched_patient['region'] !== '' ? htmlspecialchars($searched_patient['region']) : 'Not specified'; ?></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <span class="meta-item">
                                <span>📝</span>
                                <span><?php echo $searched_patient['notes_count']; ?> Notes</span>
                            </span>
                            <span class="meta-item">
                                <span>🚨</span>
                                <span><?php echo $searched_patient['unread_alerts']; ?> Alerts</span>
                            </span>
                        </div>
                    </div>

                    <div class="tab-navigation">
                        <button class="tab active" onclick="showTab('history')">Medical History</button>
                        <button class="tab" onclick="showTab('medications')">Medications</button>
                        <button class="tab" onclick="showTab('allergies')">Allergies</button>
                        <button class="tab" onclick="showTab('lab_results')">Lab Results</button>
                        <button class="tab" onclick="showTab('notes')">Doctor Notes</button>
                        <button class="tab" onclick="showTab('bills')">Bills</button>
                    </div>

                    <div id="history" class="tab-content">
                        <?php if ($medical_history && mysqli_num_rows($medical_history) > 0): ?>
                            <?php while ($history = mysqli_fetch_assoc($medical_history)): ?>
                                <div class="medical-history-item">
                                    <div class="medical-history-header">
                                        <div>
                                            <div class="medical-history-title">
                                                <?php 
                                                $condition_name = '';
                                                if ($history['source_table'] == 'patient_disease_history') {
                                                    $condition_name = 'Disease ID: ' . $history['disease_id'];
                                                } else {
                                                    $condition_name = 'Medical Condition';
                                                }
                                                echo htmlspecialchars($condition_name); 
                                                ?>
                                            </div>
                                            <div class="medical-history-meta">
                                                <span class="severity-badge severity-<?php echo strtolower($history['severity']); ?>">
                                                    <?php echo htmlspecialchars($history['severity']); ?>
                                                </span>
                                                <span class="status-badge status-<?php echo strtolower($history['status']); ?>">
                                                    <?php echo htmlspecialchars($history['status']); ?>
                                                </span>
                                                <span class="source-<?php echo $history['source'] == 'Doctor Added' ? 'doctor' : ($history['source'] == 'Medofolio Patient' ? 'medofolio' : 'patient'); ?>">
                                                    <?php echo $history['source']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="medical-history-date">
                                            📅 <?php echo date('M j, Y', strtotime($history['diagnosis_date'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="medical-history-content">
                                        <div class="medical-history-field">
                                            <div class="medical-history-label">📋 Description</div>
                                            <div class="medical-history-value"><?php echo htmlspecialchars($history['description']); ?></div>
                                        </div>
                                        
                                        <div class="medical-history-field">
                                            <div class="medical-history-label">💊 Treatment Plan</div>
                                            <div class="medical-history-value"><?php echo htmlspecialchars($history['treatment_plan']); ?></div>
                                        </div>
                                        
                                        <?php if ($history['file_name']): ?>
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">📁 Medical File</div>
                                                <div class="medical-history-value">
                                                    <a href="<?php echo htmlspecialchars($history['file_path']); ?>" target="_blank" class="file-link">
                                                        📎 <?php echo htmlspecialchars($history['file_name']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($history['treating_hospital']): ?>
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">🏥 Treating Hospital</div>
                                                <div class="medical-history-value"><?php echo htmlspecialchars($history['treating_hospital']); ?></div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($history['doctor_name']): ?>
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">👨‍⚕️ Doctor</div>
                                                <div class="medical-history-value"><?php echo htmlspecialchars($history['doctor_name']); ?> (<?php echo htmlspecialchars($history['specialization']); ?>)</div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($history['follow_up_required']): ?>
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">🔄 Follow-up</div>
                                                <div class="medical-history-value">
                                                    <?php echo $history['follow_up_date'] ? date('M j, Y', strtotime($history['follow_up_date'])) : 'Required'; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    
                    
                    </div>
                                    
                                    <div class="medical-history-actions">
                                        <button class="btn-medical-edit" onclick="showEditForm(<?php echo $history['id']; ?>, '<?php echo $history['source_table']; ?>')">
                                            ✏️ Edit Medical History
                                        </button>
                                        <?php if ($history['source_table'] != 'patient_disease_history'): ?>
                                            <button class="btn-medical-delete" onclick="confirmDelete(<?php echo $history['id']; ?>, '<?php echo $history['source_table']; ?>')">
                                                🗑️ Delete Record
                                            </button>
                                        <?php endif; ?>
                                    
                    
                    </div>
                                    
                                    <!-- Edit Form (Hidden by default) -->
                                    <div id="edit-form-<?php echo $history['id']; ?>-<?php echo $history['source_table']; ?>" class="edit-form-section" style="display: none;">
                                        <div class="section-header">
                                            <h4 class="section-title">Edit Medical History</h4>
                                        </div>
                                        <form method="POST" class="medical-form">
                                            <input type="hidden" name="history_id" value="<?php echo $history['id']; ?>">
                                            <input type="hidden" name="source_table" value="<?php echo $history['source_table']; ?>">
                                            <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="severity_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>">Severity *</label>
                                                    <select id="severity_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>" name="severity" required>
                                                        <option value="Low" <?php echo $history['severity'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                                                        <option value="Medium" <?php echo $history['severity'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                                        <option value="High" <?php echo $history['severity'] == 'High' ? 'selected' : ''; ?>>High</option>
                                                        <option value="Critical" <?php echo $history['severity'] == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="status_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>">Status *</label>
                                                    <select id="status_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>" name="status" required>
                                                        <option value="Active" <?php echo $history['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="Recovered" <?php echo $history['status'] == 'Recovered' ? 'selected' : ''; ?>>Recovered</option>
                                                        <option value="Critical" <?php echo $history['status'] == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                                                        <option value="resolved" <?php echo $history['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="description_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>">Description *</label>
                                                <textarea id="description_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>" name="description" rows="3" required><?php echo htmlspecialchars($history['description']); ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="treatment_plan_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>">Treatment Plan *</label>
                                                <textarea id="treatment_plan_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>" name="treatment_plan" rows="3" required><?php echo htmlspecialchars($history['treatment_plan']); ?></textarea>
                                            </div>
                                            <?php if ($history['source_table'] == 'patient_disease_history'): ?>
                                                <div class="form-group">
                                                    <label for="treating_hospital_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>">Treating Hospital</label>
                                                    <input type="text" id="treating_hospital_<?php echo $history['id']; ?>_<?php echo $history['source_table']; ?>" name="treating_hospital" value="<?php echo htmlspecialchars($history['treating_hospital']); ?>">
                                                </div>
                                            <?php endif; ?>
                                            <div class="form-actions">
                                                <button type="submit" name="edit_medical_history" class="btn-primary">💾 Save Changes</button>
                                                <button type="button" class="btn-secondary" onclick="hideEditForm(<?php echo $history['id']; ?>, '<?php echo $history['source_table']; ?>')">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Delete Form (Hidden) -->
                                    <form id="delete-form-<?php echo $history['id']; ?>-<?php echo $history['source_table']; ?>" method="POST" style="display: none;">
                                        <input type="hidden" name="history_id" value="<?php echo $history['id']; ?>">
                                        <input type="hidden" name="source_table" value="<?php echo $history['source_table']; ?>">
                                        <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                                        <input type="hidden" name="delete_medical_history" value="1">
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <h3>No Medical History</h3>
                                <p>No medical history has been recorded for this patient yet.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="section-header" style="margin-top: 30px;">
                            <h3 class="section-title">Add Medical History</h3>
                        </div>
                        <form method="POST" enctype="multipart/form-data" class="medical-form">
                            <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="condition_name">Condition Name *</label>
                                    <input type="text" id="condition_name" name="condition_name" placeholder="e.g., Hypertension" required>
                                </div>
                                <div class="form-group">
                                    <label for="diagnosis_date">Diagnosis Date *</label>
                                    <input type="date" id="diagnosis_date" name="diagnosis_date" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="severity">Severity *</label>
                                    <select id="severity" name="severity" required>
                                        <option value="">Select Severity</option>
                                        <option value="mild">Mild</option>
                                        <option value="moderate">Moderate</option>
                                        <option value="severe">Severe</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="follow_up_date">Follow-up Date</label>
                                    <input type="date" id="follow_up_date" name="follow_up_date">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="description">Description *</label>
                                <textarea id="description" name="description" rows="3" placeholder="Describe the condition..." required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="treatment_plan">Treatment Plan *</label>
                                <textarea id="treatment_plan" name="treatment_plan" rows="3" placeholder="Describe the treatment plan..." required></textarea>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="follow_up_required" id="follow_up_required">
                                    Follow-up required
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="medical_file">Upload Medical File (PDF, JPG, PNG)</label>
                                <input type="file" id="medical_file" name="medical_file" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_medical_history" class="btn-primary">➕ Add Medical History</button>
                            </div>
                        </form>
                    </div>
                    
                    <div id="medications" class="tab-content">
                        <?php if ($medications && mysqli_num_rows($medications) > 0): ?>
                            <?php while ($med = mysqli_fetch_assoc($medications)): ?>
                                <div class="note-item">
                                    <div class="note-header">
                                        <div class="note-doctor">
                                            💊 <strong><?php echo htmlspecialchars($med['medication_name']); ?></strong> - <?php echo htmlspecialchars($med['dosage']); ?>
                                        </div>
                                        <div class="note-date">📅 Started: <?php echo date('M j, Y', strtotime($med['start_date'])); ?></div>
                                    </div>
                                    <div class="note-content">
                                        <div class="medication-details">
                                            <div class="detail-row">
                                                <span class="detail-label">Frequency:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($med['frequency']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Route:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($med['route']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Purpose:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($med['purpose']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Prescribed by:</span>
                                                <span class="detail-value">🩺 Dr. <?php echo htmlspecialchars($med['prescribing_doctor']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Status:</span>
                                                <span class="detail-value status-badge status-<?php echo strtolower($med['status']); ?>"><?php echo htmlspecialchars($med['status']); ?></span>
                                            </div>
                                            <?php if ($med['end_date']): ?>
                                            <div class="detail-row">
                                                <span class="detail-label">End Date:</span>
                                                <span class="detail-value">📅 <?php echo date('M j, Y', strtotime($med['end_date'])); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="note-actions">
                                        <button class="btn-small btn-primary" onclick="showEditMedicationForm(<?php echo $med['id']; ?>)">✏️ Edit</button>
                                        <button class="btn-small btn-danger" onclick="confirmDeleteMedication(<?php echo $med['id']; ?>)">🗑️ Delete</button>
                                    </div>
                                    
                                    <!-- Edit Form (Hidden by default) -->
                                    <div id="edit-medication-<?php echo $med['id']; ?>" style="display: none;" class="edit-form">
                                        <form method="POST" class="medical-form">
                                            <input type="hidden" name="medication_id" value="<?php echo $med['id']; ?>">
                                            <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="edit_medication_name_<?php echo $med['id']; ?>">Medication Name *</label>
                                                    <input type="text" id="edit_medication_name_<?php echo $med['id']; ?>" name="medication_name" value="<?php echo htmlspecialchars($med['medication_name']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="edit_dosage_<?php echo $med['id']; ?>">Dosage *</label>
                                                    <input type="text" id="edit_dosage_<?php echo $med['id']; ?>" name="dosage" value="<?php echo htmlspecialchars($med['dosage']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="edit_frequency_<?php echo $med['id']; ?>">Frequency *</label>
                                                    <input type="text" id="edit_frequency_<?php echo $med['id']; ?>" name="frequency" value="<?php echo htmlspecialchars($med['frequency']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="edit_route_<?php echo $med['id']; ?>">Route *</label>
                                                    <select id="edit_route_<?php echo $med['id']; ?>" name="route" required>
                                                        <option value="oral" <?php echo $med['route'] == 'oral' ? 'selected' : ''; ?>>Oral</option>
                                                        <option value="injectable" <?php echo $med['route'] == 'injectable' ? 'selected' : ''; ?>>Injectable</option>
                                                        <option value="topical" <?php echo $med['route'] == 'topical' ? 'selected' : ''; ?>>Topical</option>
                                                        <option value="inhalation" <?php echo $med['route'] == 'inhalation' ? 'selected' : ''; ?>>Inhalation</option>
                                                        <option value="other" <?php echo $med['route'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="edit_start_date_<?php echo $med['id']; ?>">Start Date *</label>
                                                    <input type="date" id="edit_start_date_<?php echo $med['id']; ?>" name="start_date" value="<?php echo $med['start_date']; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="edit_end_date_<?php echo $med['id']; ?>">End Date</label>
                                                    <input type="date" id="edit_end_date_<?php echo $med['id']; ?>" name="end_date" value="<?php echo $med['end_date']; ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit_purpose_<?php echo $med['id']; ?>">Purpose *</label>
                                                <textarea id="edit_purpose_<?php echo $med['id']; ?>" name="purpose" rows="2" required><?php echo htmlspecialchars($med['purpose']); ?></textarea>
                                            </div>
                                            <div class="form-actions">
                                                <button type="submit" name="edit_medication" class="btn-primary">💾 Update Medication</button>
                                                <button type="button" class="btn-secondary" onclick="hideEditMedicationForm(<?php echo $med['id']; ?>)">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Delete Form -->
                                    <form id="delete-medication-<?php echo $med['id']; ?>" method="POST" style="display: none;">
                                        <input type="hidden" name="medication_id" value="<?php echo $med['id']; ?>">
                                        <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                                        <input type="hidden" name="delete_medication" value="1">
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <h3>No Active Medications</h3>
                                <p>No active medications have been prescribed for this patient.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="section-header" style="margin-top: 30px;">
                            <h3 class="section-title">Prescribe Medication</h3>
                        </div>
                        <form method="POST" enctype="multipart/form-data" class="medical-form">
                            <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="medication_name">Medication Name *</label>
                                    <input type="text" id="medication_name" name="medication_name" placeholder="e.g., Aspirin" required>
                                </div>
                                <div class="form-group">
                                    <label for="dosage">Dosage *</label>
                                    <input type="text" id="dosage" name="dosage" placeholder="e.g., 100mg" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="frequency">Frequency *</label>
                                    <input type="text" id="frequency" name="frequency" placeholder="e.g., Twice daily" required>
                                </div>
                                <div class="form-group">
                                    <label for="route">Route *</label>
                                    <select id="route" name="route" required>
                                        <option value="">Select Route</option>
                                        <option value="oral">Oral</option>
                                        <option value="injectable">Injectable</option>
                                        <option value="topical">Topical</option>
                                        <option value="inhalation">Inhalation</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start_date">Start Date *</label>
                                    <input type="date" id="start_date" name="start_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_date">End Date</label>
                                    <input type="date" id="end_date" name="end_date">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="purpose">Purpose *</label>
                                <textarea id="purpose" name="purpose" rows="2" placeholder="Reason for prescription..." required></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="pharmacy_id">Select Pharmacy</label>
                                    <select id="pharmacy_id" name="pharmacy_id">
                                        <option value="">Select Pharmacy (Optional)</option>
                                        <?php if ($pharmacies && mysqli_num_rows($pharmacies) > 0): ?>
                                            <?php while ($pharmacy = mysqli_fetch_assoc($pharmacies)): ?>
                                                <option value="<?php echo $pharmacy['pharmacy_id']; ?>">
                                                    <?php echo htmlspecialchars($pharmacy['pharmacy_name']); ?> - <?php echo htmlspecialchars($pharmacy['address']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="quantity">Quantity</label>
                                    <input type="number" id="quantity" name="quantity" placeholder="e.g., 30" min="1" value="30">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="send_to_pharmacy" id="send_to_pharmacy">
                                        <span class="checkmark"></span>
                                        🏪 Send prescription to pharmacy
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="generate_bill" id="generate_bill" checked>
                                        <span class="checkmark"></span>
                                        💳 Generate bill for patient (Recommended)
                                    </label>
                                </div>
                                <p class="form-help-text">
                                    💡 Tip: Generating bills automatically creates payment records and helps track patient expenses
                                </p>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="add_medication" class="btn-primary">➕ Prescribe Medication</button>
                            </div>
                        </form>
                    </div>

                    <div id="allergies" class="tab-content">
                        <?php if ($allergies && mysqli_num_rows($allergies) > 0): ?>
                            <?php while ($allergy = mysqli_fetch_assoc($allergies)): ?>
                                <div class="alert-item <?php echo $allergy['severity']; ?>">
                                    <div class="alert-header">
                                        <div class="alert-patient"><?php echo htmlspecialchars($allergy['allergen']); ?> - <?php echo htmlspecialchars($allergy['allergy_type']); ?></div>
                                        <div class="alert-time">Diagnosed: <?php echo date('M j, Y', strtotime($allergy['diagnosed_date'])); ?></div>
                                    </div>
                                    <div class="alert-message">
                                        <strong>Reaction:</strong> <?php echo htmlspecialchars($allergy['reaction']); ?><br>
                                        <strong>Severity:</strong> <?php echo htmlspecialchars($allergy['severity']); ?><br>
                                        <strong>Recorded by:</strong> <?php echo htmlspecialchars($allergy['doctor_name']); ?>
                                        <?php if ($allergy['notes']): ?>
                                            <br><strong>Notes:</strong> <?php echo htmlspecialchars($allergy['notes']); ?>
                                        <?php endif; ?>
                                    
                    
                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <h3>No Allergies Recorded</h3>
                                <p>No allergies have been recorded for this patient.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="section-header" style="margin-top: 30px;">
                            <h3 class="section-title">Add Allergy</h3>
                        </div>
                        <form method="POST" enctype="multipart/form-data" class="medical-form">
                            <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="allergen">Allergen *</label>
                                    <input type="text" id="allergen" name="allergen" placeholder="e.g., Penicillin" required>
                                </div>
                                <div class="form-group">
                                    <label for="allergy_type">Allergy Type *</label>
                                    <select id="allergy_type" name="allergy_type" required>
                                        <option value="">Select Type</option>
                                        <option value="drug">Drug</option>
                                        <option value="food">Food</option>
                                        <option value="environmental">Environmental</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="severity">Severity *</label>
                                    <select id="severity" name="severity" required>
                                        <option value="">Select Severity</option>
                                        <option value="mild">Mild</option>
                                        <option value="moderate">Moderate</option>
                                        <option value="severe">Severe</option>
                                        <option value="life_threatening">Life-threatening</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="diagnosed_date">Diagnosed Date *</label>
                                    <input type="date" id="diagnosed_date" name="diagnosed_date" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="reaction">Reaction *</label>
                                <textarea id="reaction" name="reaction" rows="2" placeholder="Describe the allergic reaction..." required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="allergy_notes">Notes</label>
                                <textarea id="allergy_notes" name="allergy_notes" rows="2" placeholder="Additional notes..."></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_allergy" class="btn-primary">➕ Add Allergy</button>
                            </div>
                        </form>
                    </div>

                    <div id="lab_results" class="tab-content">
                        <?php if ($lab_results && mysqli_num_rows($lab_results) > 0): ?>
                            <?php while ($lab = mysqli_fetch_assoc($lab_results)): ?>
                                <div class="medical-history-item">
                                    <div class="medical-history-header">
                                        <div>
                                            <div class="medical-history-title">
                                                📄 <?php echo htmlspecialchars($lab['test_name']); ?>
                                                <?php if ($lab['source_table'] == 'medical_reports'): ?>
                                                    <small style="color: #666;">(Medical Report)</small>
                                                <?php endif; ?>
                                            
                    
                    </div>
                                            <div class="medical-history-meta">
                                                <span class="status-badge status-<?php echo strtolower($lab['status']); ?>">
                                                    <?php echo htmlspecialchars($lab['status']); ?>
                                                </span>
                                                <span class="source-<?php echo $lab['source'] == 'Doctor Added' ? 'doctor' : ($lab['source'] == 'Medofolio Patient' ? 'medofolio' : 'patient'); ?>">
                                                    <?php echo $lab['source']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="medical-history-date">
                                            📅 <?php echo date('M j, Y', strtotime($lab['test_date'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="medical-history-content">
                                        <?php if ($lab['source_table'] == 'medical_reports'): ?>
                                            <!-- Medofolio Medical Report -->
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">📋 Disease/Condition</div>
                                                <div class="medical-history-value"><?php echo htmlspecialchars($lab['test_name']); ?></div>
                                            </div>
                                            
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">📁 File</div>
                                                <div class="medical-history-value">
                                                    <?php if ($lab['file_path']): ?>
                                                        <a href="<?php echo htmlspecialchars($lab['file_path']); ?>" target="_blank" class="file-link">
                                                            📎 <?php echo htmlspecialchars($lab['file_name']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        No file available
                                                    <?php endif; ?>
                                                
                    
                    </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Lab Result -->
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">🧪 Test Type</div>
                                                <div class="medical-history-value"><?php echo htmlspecialchars($lab['test_type']); ?></div>
                                            </div>
                                            
                                            <?php if ($lab['result_value']): ?>
                                                <div class="medical-history-field">
                                                    <div class="medical-history-label">📊 Result Value</div>
                                                    <div class="medical-history-value"><?php echo htmlspecialchars($lab['result_value']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($lab['normal_range']): ?>
                                                <div class="medical-history-field">
                                                    <div class="medical-history-label">📏 Normal Range</div>
                                                    <div class="medical-history-value"><?php echo htmlspecialchars($lab['normal_range']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($lab['reported_by']): ?>
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">👨‍⚕️ Reported By</div>
                                                <div class="medical-history-value"><?php echo htmlspecialchars($lab['reported_by']); ?></div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($lab['doctor_name']): ?>
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">👨‍⚕️ Doctor</div>
                                                <div class="medical-history-value"><?php echo htmlspecialchars($lab['doctor_name']); ?> (<?php echo htmlspecialchars($lab['specialization']); ?>)</div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($lab['notes']): ?>
                                            <div class="medical-history-field">
                                                <div class="medical-history-label">📝 Notes</div>
                                                <div class="medical-history-value"><?php echo nl2br(htmlspecialchars($lab['notes'])); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    
                    
                    </div>
                                    
                                    <div class="medical-history-actions">
                                        <?php if ($lab['source_table'] == 'medical_reports' && $lab['file_path']): ?>
                                            <a href="<?php echo htmlspecialchars($lab['file_path']); ?>" target="_blank" class="btn-medical-edit">
                                                👁️ View Report
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($lab['source_table'] != 'medical_reports'): ?>
                                            <button class="btn-medical-edit" onclick="showEditLabForm(<?php echo $lab['id']; ?>)">
                                                ✏️ Edit Lab Result
                                            </button>
                                            <button class="btn-medical-delete" onclick="confirmDeleteLab(<?php echo $lab['id']; ?>)">
                                                🗑️ Delete Result
                                            </button>
                                        <?php endif; ?>
                                    
                    
                    </div>
                                    
                                    <!-- Edit Lab Form (Hidden by default) -->
                                    <div id="edit-lab-form-<?php echo $lab['id']; ?>" class="edit-form-section" style="display: none;">
                                        <div class="section-header">
                                            <h4 class="section-title">Edit Lab Result</h4>
                                        </div>
                                        <form method="POST" class="medical-form">
                                            <input type="hidden" name="lab_id" value="<?php echo $lab['id']; ?>">
                                            <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="test_name_<?php echo $lab['id']; ?>">Test Name *</label>
                                                    <input type="text" id="test_name_<?php echo $lab['id']; ?>" name="test_name" value="<?php echo htmlspecialchars($lab['test_name']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="test_type_<?php echo $lab['id']; ?>">Test Type *</label>
                                                    <input type="text" id="test_type_<?php echo $lab['id']; ?>" name="test_type" value="<?php echo htmlspecialchars($lab['test_type']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="result_value_<?php echo $lab['id']; ?>">Result Value</label>
                                                    <input type="text" id="result_value_<?php echo $lab['id']; ?>" name="result_value" value="<?php echo htmlspecialchars($lab['result_value']); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label for="normal_range_<?php echo $lab['id']; ?>">Normal Range</label>
                                                    <input type="text" id="normal_range_<?php echo $lab['id']; ?>" name="normal_range" value="<?php echo htmlspecialchars($lab['normal_range']); ?>">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label for="status_<?php echo $lab['id']; ?>">Status *</label>
                                                    <select id="status_<?php echo $lab['id']; ?>" name="status" required>
                                                        <option value="normal" <?php echo $lab['status'] == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                                        <option value="abnormal" <?php echo $lab['status'] == 'abnormal' ? 'selected' : ''; ?>>Abnormal</option>
                                                        <option value="critical" <?php echo $lab['status'] == 'critical' ? 'selected' : ''; ?>>Critical</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="test_date_<?php echo $lab['id']; ?>">Test Date *</label>
                                                    <input type="date" id="test_date_<?php echo $lab['id']; ?>" name="test_date" value="<?php echo $lab['test_date']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="notes_<?php echo $lab['id']; ?>">Notes</label>
                                                <textarea id="notes_<?php echo $lab['id']; ?>" name="notes" rows="3"><?php echo htmlspecialchars($lab['notes']); ?></textarea>
                                            </div>
                                            <div class="form-actions">
                                                <button type="submit" name="edit_lab_result" class="btn-primary">💾 Save Changes</button>
                                                <button type="button" class="btn-secondary" onclick="hideEditLabForm(<?php echo $lab['id']; ?>)">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Delete Form (Hidden) -->
                                    <form id="delete-lab-form-<?php echo $lab['id']; ?>" method="POST" style="display: none;">
                                        <input type="hidden" name="lab_id" value="<?php echo $lab['id']; ?>">
                                        <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                                        <input type="hidden" name="delete_lab_result" value="1">
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <h3>No Lab Results</h3>
                                <p>No lab results have been recorded for this patient.</p>
                            </div>
                        <?php endif; ?>
                    
                    
                    
                    
                    <!-- Doctor Lab Result Upload Form -->
                    <div class="section-header" style="margin-top: 30px;">
                        <h3 class="section-title">📊 Upload Lab Result</h3>
                        <p class="section-subtitle">Add new lab results or medical reports for this patient</p>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="medical-form">
                        <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lab_test_name">Test Name *</label>
                                <input type="text" id="lab_test_name" name="test_name" placeholder="e.g., Complete Blood Count" required>
                            </div>
                            <div class="form-group">
                                <label for="lab_test_type">Test Type *</label>
                                <input type="text" id="lab_test_type" name="test_type" placeholder="e.g., Blood Test" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lab_result_value">Result Value</label>
                                <input type="text" id="lab_result_value" name="result_value" placeholder="e.g., 120/80 mmHg">
                            </div>
                            <div class="form-group">
                                <label for="lab_normal_range">Normal Range</label>
                                <input type="text" id="lab_normal_range" name="normal_range" placeholder="e.g., 90-120/60-80 mmHg">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="lab_status">Result Status *</label>
                                <select id="lab_status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="normal">Normal</option>
                                    <option value="abnormal">Abnormal</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="lab_test_date">Test Date *</label>
                                <input type="date" id="lab_test_date" name="test_date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="lab_reported_by">Reported By</label>
                            <input type="text" id="lab_reported_by" name="reported_by" placeholder="Lab/Hospital Name">
                        </div>
                        <div class="form-group">
                            <label for="lab_notes">Notes</label>
                            <textarea id="lab_notes" name="notes" rows="3" placeholder="Additional notes about the test..."></textarea>
                        </div>
                        <div class="file-upload">
                            <p>📁 Upload Lab Report File (PDF, JPG, PNG)</p>
                            <input type="file" name="lab_file" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="form-actions" style="margin-top: 20px;">
                            <button type="submit" name="add_lab_result" class="btn-primary">📊 Upload Lab Result</button>
                        </div>
                    </form></div>

                    <div id="notes" class="tab-content">
                        <?php if (mysqli_num_rows($doctor_notes) > 0): ?>
                            <?php while ($note = mysqli_fetch_assoc($doctor_notes)): ?>
                                <div class="note-item">
                                    <div class="note-header">
                                        <div class="note-doctor">Dr. <?php echo htmlspecialchars($note['doctor_name']); ?> (<?php echo htmlspecialchars($note['specialization']); ?>)</div>
                                        <div class="note-date"><?php echo date('M j, Y H:i', strtotime($note['created_at'])); ?></div>
                                    </div>
                                    <div class="note-content">
                                        <div class="note-diagnosis">Diagnosis: <?php echo htmlspecialchars($note['diagnosis']); ?></div>
                                        <div class="note-prescription">Prescription: <?php echo htmlspecialchars($note['prescription']); ?></div>
                                        <?php if ($note['follow_up_date']): ?>
                                            <div class="note-prescription">Follow-up: <?php echo date('M j, Y', strtotime($note['follow_up_date'])); ?></div>
                                        <?php endif; ?>
                                    
                    
                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <h3>No Doctor Notes</h3>
                                <p>No doctor notes have been recorded for this patient yet.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="section-header" style="margin-top: 30px;">
                            <h3 class="section-title">Add Doctor Note</h3>
                        </div>
                        <form method="POST" class="medical-form">
                            <input type="hidden" name="patient_id" value="<?php echo $searched_patient['patient_id']; ?>">
                            <div class="form-group">
                                <label for="note_content">Note Content *</label>
                                <textarea id="note_content" name="note_content" rows="4" placeholder="Enter your clinical notes, observations, diagnosis, or treatment recommendations..." required></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="note_type">Note Type</label>
                                    <select id="note_type" name="note_type">
                                        <option value="clinical">Clinical Note</option>
                                        <option value="observation">Observation</option>
                                        <option value="prescription">Prescription</option>
                                        <option value="follow-up">Follow-up</option>
                                        <option value="referral">Referral</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="add_doctor_note" class="btn-primary">📝 Add Doctor Note</button>
                            </div>
                        </form>
                    
                    </div>

                    <div id="bills" class="tab-content">
                        <?php if ($bills && mysqli_num_rows($bills) > 0): ?>
                            <?php while ($bill = mysqli_fetch_assoc($bills)): ?>
                                <div class="bill-item">
                                    <div class="bill-header">
                                        <div class="bill-title">
                                            <?php echo htmlspecialchars($bill['description']); ?>
                                            <?php if ($bill['order_id']): ?>
                                                <span class="order-id">Order #<?php echo $bill['order_id']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="bill-amount">₹<?php echo number_format($bill['total_amount'], 2); ?></div>
                                    </div>
                                    <div class="bill-details">
                                        <div class="bill-info">
                                            <div class="detail-row">
                                                <span class="detail-label">Bill Type:</span>
                                                <span class="detail-value"><?php echo ucfirst($bill['bill_type']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Date:</span>
                                                <span class="detail-value">📅 <?php echo date('M j, Y', strtotime($bill['billing_date'])); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Due Date:</span>
                                                <span class="detail-value">⏰ <?php echo date('M j, Y', strtotime($bill['due_date'])); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Status:</span>
                                                <span class="detail-value">
                                                    <span class="payment-status <?php echo strtolower($bill['payment_status']); ?>">
                                                        <?php echo ucfirst($bill['payment_status']); ?>
                                                    </span>
                                                </span>
                                            </div>
                                            <?php if ($bill['order_status']): ?>
                                            <div class="detail-row">
                                                <span class="detail-label">Order Status:</span>
                                                <span class="detail-value"><?php echo ucfirst($bill['order_status']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($bill['payment_status'] == 'Pending'): ?>
                                            <div class="bill-actions">
                                                <div class="pending-indicator">
                                                    <span class="status-badge status-pending">⏳ Pending Payment</span>
                                                    <small>Patient needs to pay this bill</small>
                                                </div>
                                            </div>
                                        <?php elseif ($bill['payment_status'] == 'Paid'): ?>
                                            <div class="payment-info">
                                                <span class="payment-method">✅ Paid via <?php echo ucfirst(str_replace('_', ' ', $bill['payment_method'])); ?></span>
                                            </div>
                                        <?php elseif ($bill['payment_status'] == 'Overdue'): ?>
                                            <div class="bill-actions">
                                                <div class="overdue-indicator">
                                                    <span class="status-badge status-overdue">🔴 Overdue</span>
                                                    <small>Payment is overdue</small>
                                                </div>
                                            </div>
                                        <?php elseif ($bill['payment_status'] == 'Due Today'): ?>
                                            <div class="bill-actions">
                                                <div class="due-today-indicator">
                                                    <span class="status-badge status-due-today">🟡 Due Today</span>
                                                    <small>Payment due today</small>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <h3>No Bills</h3>
                                <p>No bills have been generated for this patient.</p>
                                <div class="empty-state-actions">
                                    <p><strong>To create bills:</strong></p>
                                    <ol>
                                        <li>Go to the <strong>Medications</strong> tab</li>
                                        <li>Prescribe a medication with <strong>"Generate bill for patient"</strong> checked</li>
                                        <li>The bill will appear here automatically</li>
                                    </ol>
                                    <p><em>💡 Bills are automatically created when you prescribe medications and check the "Generate bill" option.</em></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    
                                    </div>
            <?php endif; ?>

            <div class="main-content">
                <div class="section">
                    <div class="section-header">
                        <h3 class="section-title">Recent Doctor Notes</h3>
                    </div>
                    <?php if (mysqli_num_rows($recent_notes) > 0): ?>
                        <?php while ($note = mysqli_fetch_assoc($recent_notes)): ?>
                            <div class="note-item">
                                <div class="note-header">
                                    <div class="note-doctor"><?php echo htmlspecialchars($note['patient_name']); ?> (<?php echo date('Y') - date('Y', strtotime($note['dob'])); ?> years, <?php echo htmlspecialchars($note['gender']); ?>)</div>
                                    <div class="note-date"><?php echo date('M j, Y', strtotime($note['created_at'])); ?></div>
                                </div>
                                <div class="note-content">
                                    <div class="note-diagnosis"><?php echo htmlspecialchars(substr($note['diagnosis'], 0, 100)); ?>...</div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>No Recent Notes</h3>
                            <p>You haven't added any doctor notes recently.</p>
                        </div>
                    <?php endif; ?>
                
                    
                    </div>

                <div class="section">
                    <div class="section-header">
                        <h3 class="section-title">Recent Alerts</h3>
                    </div>
                    <?php if (mysqli_num_rows($recent_alerts) > 0): ?>
                        <?php while ($alert = mysqli_fetch_assoc($recent_alerts)): ?>
                            <div class="alert-item <?php echo $alert['severity']; ?>">
                                <div class="alert-header">
                                    <div class="alert-patient"><?php echo htmlspecialchars($alert['patient_name']); ?></div>
                                    <div class="alert-time"><?php echo date('M j, Y H:i', strtotime($alert['created_at'])); ?></div>
                                </div>
                                <div class="alert-message"><?php echo htmlspecialchars(substr($alert['message'], 0, 80)); ?>...</div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>No Recent Alerts</h3>
                            <p>No alerts have been created recently.</p>
                        </div>
                    <?php endif; ?>
                
                    
                    </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        function showEditForm(historyId, sourceTable) {
            document.getElementById('edit-form-' + historyId + '-' + sourceTable).style.display = 'block';
        }

        function hideEditForm(historyId, sourceTable) {
            document.getElementById('edit-form-' + historyId + '-' + sourceTable).style.display = 'none';
        }

        function confirmDelete(historyId, sourceTable) {
            if (sourceTable === 'patient_disease_history') {
                alert('Cannot delete Medofolio patient data. This data is protected.');
                return;
            }
            if (confirm('Are you sure you want to delete this medical history record?')) {
                document.getElementById('delete-form-' + historyId + '-' + sourceTable).submit();
            }
        }

        function showEditMedicationForm(medicationId) {
            document.getElementById('edit-medication-' + medicationId).style.display = 'block';
        }

        function hideEditMedicationForm(medicationId) {
            document.getElementById('edit-medication-' + medicationId).style.display = 'none';
        }

        function confirmDeleteMedication(medicationId) {
            if (confirm('Are you sure you want to delete this medication? This action cannot be undone.')) {
                document.getElementById('delete-medication-' + medicationId).submit();
            }
        }

        function showEditLabForm(labId) {
            document.getElementById('edit-lab-form-' + labId).style.display = 'block';
        }

        function hideEditLabForm(labId) {
            document.getElementById('edit-lab-form-' + labId).style.display = 'none';
        }

        function confirmDeleteLab(labId) {
            if (confirm('Are you sure you want to delete this lab result?')) {
                document.getElementById('delete-lab-form-' + labId).submit();
            }
        }

        // Auto-refresh alerts every 30 seconds
        setInterval(function() {
            // You can implement AJAX refresh here if needed
        }, 30000);

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.focus();
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>
</html>
