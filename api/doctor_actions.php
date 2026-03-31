<?php
/**
 * Doctor Actions API
 * Handles all doctor-related actions including notes, alerts, and patient assignments
 */

session_start();
include "../db.php";

// Check if doctor is logged in
if (!isset($_SESSION['doctor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_note':
        addDoctorNote();
        break;
    case 'assign_patient':
        assignPatientToDoctor();
        break;
    case 'create_alert':
        createPatientAlert();
        break;
    case 'get_patients':
        getDoctorPatients();
        break;
    case 'get_patient_details':
        getPatientDetails();
        break;
    case 'update_alert_status':
        updateAlertStatus();
        break;
    case 'get_risk_assessment':
        getRiskAssessment();
        break;
    case 'get_ai_summary':
        getAISummary();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function addDoctorNote() {
    global $conn, $doctor_id;
    
    $patient_id = (int)$_POST['patient_id'];
    $diagnosis = trim($_POST['diagnosis']);
    $prescription = trim($_POST['prescription']);
    $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
    $severity = $_POST['severity'] ?? 'medium';
    
    // Validate required fields
    if (empty($patient_id) || empty($diagnosis) || empty($prescription)) {
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    // Check if doctor has access to this patient
    $access_check = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM patient_access WHERE patient_id = $patient_id AND doctor_id = $doctor_id AND is_active = 1"
    ))['count'] ?? 0;
    
    if ($access_check == 0) {
        echo json_encode(['error' => 'You do not have access to this patient']);
        return;
    }
    
    // Add the note
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO doctor_notes (patient_id, doctor_id, diagnosis, prescription, follow_up_date, severity) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "iissss", $patient_id, $doctor_id, $diagnosis, $prescription, $follow_up_date, $severity);
    
    if (mysqli_stmt_execute($stmt)) {
        // Log the activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'add_note', ?)"
        );
        $details = "Added note: " . substr($diagnosis, 0, 50) . "...";
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);
        
        echo json_encode(['success' => true, 'message' => 'Doctor note added successfully']);
    } else {
        echo json_encode(['error' => 'Failed to add note']);
    }
}

function assignPatientToDoctor() {
    global $conn, $doctor_id;
    
    $patient_id = (int)$_POST['patient_id'];
    
    if (empty($patient_id)) {
        echo json_encode(['error' => 'Patient ID is required']);
        return;
    }
    
    // Check if patient exists
    $patient_check = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM patient WHERE patient_id = $patient_id"
    ))['count'] ?? 0;
    
    if ($patient_check == 0) {
        echo json_encode(['error' => 'Patient not found']);
        return;
    }
    
    // Assign patient (INSERT IGNORE prevents duplicates)
    $stmt = mysqli_prepare($conn, 
        "INSERT IGNORE INTO patient_access (patient_id, doctor_id, assigned_by) VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "iii", $patient_id, $doctor_id, $doctor_id);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            // Log the activity
            $log_stmt = mysqli_prepare($conn, 
                "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
                 VALUES (?, ?, 'assign_patient', 'Patient assigned to doctor')"
            );
            mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
            mysqli_stmt_execute($log_stmt);
            
            echo json_encode(['success' => true, 'message' => 'Patient assigned successfully']);
        } else {
            echo json_encode(['error' => 'Patient is already assigned to you']);
        }
    } else {
        echo json_encode(['error' => 'Failed to assign patient']);
    }
}

function createPatientAlert() {
    global $conn, $doctor_id;
    
    $patient_id = (int)$_POST['patient_id'];
    $message = trim($_POST['message']);
    $severity = $_POST['severity'] ?? 'medium';
    $alert_type = $_POST['alert_type'] ?? 'system';
    
    // Validate required fields
    if (empty($patient_id) || empty($message)) {
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    // Check if doctor has access to this patient
    $access_check = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM patient_access WHERE patient_id = $patient_id AND doctor_id = $doctor_id AND is_active = 1"
    ))['count'] ?? 0;
    
    if ($access_check == 0) {
        echo json_encode(['error' => 'You do not have access to this patient']);
        return;
    }
    
    // Create the alert
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO alerts (patient_id, doctor_id, message, severity, alert_type) 
         VALUES (?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "iisss", $patient_id, $doctor_id, $message, $severity, $alert_type);
    
    if (mysqli_stmt_execute($stmt)) {
        // Log the activity
        $log_stmt = mysqli_prepare($conn, 
            "INSERT INTO doctor_activity_log (doctor_id, patient_id, activity_type, activity_details) 
             VALUES (?, ?, 'create_alert', ?)"
        );
        $details = "Created {$severity} alert: " . substr($message, 0, 50) . "...";
        mysqli_stmt_bind_param($log_stmt, "iis", $doctor_id, $patient_id, $details);
        mysqli_stmt_execute($log_stmt);
        
        echo json_encode(['success' => true, 'message' => 'Alert created successfully']);
    } else {
        echo json_encode(['error' => 'Failed to create alert']);
    }
}

function getDoctorPatients() {
    global $conn, $doctor_id;
    
    $patients = mysqli_query($conn, 
        "SELECT p.*, pa.assigned_date, 
                (SELECT COUNT(*) FROM doctor_notes WHERE patient_id = p.patient_id) as notes_count,
                (SELECT COUNT(*) FROM alerts WHERE patient_id = p.patient_id AND is_read = 0) as unread_alerts
         FROM patient p 
         JOIN patient_access pa ON p.patient_id = pa.patient_id 
         WHERE pa.doctor_id = $doctor_id AND pa.is_active = 1 
         ORDER BY pa.assigned_date DESC"
    );
    
    $result = [];
    while ($row = mysqli_fetch_assoc($patients)) {
        $result[] = $row;
    }
    
    echo json_encode(['success' => true, 'patients' => $result]);
}

function getPatientDetails() {
    global $conn, $doctor_id;
    
    $patient_id = (int)$_GET['patient_id'];
    
    // Check access
    $access_check = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM patient_access WHERE patient_id = $patient_id AND doctor_id = $doctor_id AND is_active = 1"
    ))['count'] ?? 0;
    
    if ($access_check == 0) {
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Get patient details
    $patient = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT * FROM patient WHERE patient_id = $patient_id"
    ));
    
    if (!$patient) {
        echo json_encode(['error' => 'Patient not found']);
        return;
    }
    
    // Get medical history
    $history = mysqli_query($conn, 
        "SELECT * FROM patient_disease_history WHERE patient_id = $patient_id ORDER BY detected_date DESC"
    );
    
    // Get vitals
    $vitals = mysqli_query($conn, 
        "SELECT * FROM vitals WHERE patient_id = $patient_id ORDER BY measured_at DESC LIMIT 10"
    );
    
    // Get doctor notes
    $notes = mysqli_query($conn, 
        "SELECT dn.*, dl.name as doctor_name, dl.specialization 
         FROM doctor_notes dn 
         JOIN doctor_login dl ON dn.doctor_id = dl.doctor_id 
         WHERE dn.patient_id = $patient_id 
         ORDER BY dn.created_at DESC"
    );
    
    // Get alerts
    $alerts = mysqli_query($conn, 
        "SELECT * FROM alerts WHERE patient_id = $patient_id ORDER BY created_at DESC"
    );
    
    $result = [
        'patient' => $patient,
        'medical_history' => [],
        'vitals' => [],
        'doctor_notes' => [],
        'alerts' => []
    ];
    
    while ($row = mysqli_fetch_assoc($history)) {
        $result['medical_history'][] = $row;
    }
    
    while ($row = mysqli_fetch_assoc($vitals)) {
        $result['vitals'][] = $row;
    }
    
    while ($row = mysqli_fetch_assoc($notes)) {
        $result['doctor_notes'][] = $row;
    }
    
    while ($row = mysqli_fetch_assoc($alerts)) {
        $result['alerts'][] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
}

function updateAlertStatus() {
    global $conn, $doctor_id;
    
    $alert_id = (int)$_POST['alert_id'];
    $status = $_POST['status']; // 'read' or 'resolved'
    
    if (empty($alert_id) || empty($status)) {
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    // Check if alert belongs to doctor's patient
    $alert_check = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM alerts a 
         JOIN patient_access pa ON a.patient_id = pa.patient_id 
         WHERE a.alert_id = $alert_id AND pa.doctor_id = $doctor_id"
    ))['count'] ?? 0;
    
    if ($alert_check == 0) {
        echo json_encode(['error' => 'Alert not found or access denied']);
        return;
    }
    
    if ($status === 'read') {
        $stmt = mysqli_prepare($conn, "UPDATE alerts SET is_read = 1 WHERE alert_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $alert_id);
    } elseif ($status === 'resolved') {
        $stmt = mysqli_prepare($conn, "UPDATE alerts SET is_read = 1, resolved_at = NOW(), resolved_by = ? WHERE alert_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $alert_id);
    } else {
        echo json_encode(['error' => 'Invalid status']);
        return;
    }
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Alert status updated']);
    } else {
        echo json_encode(['error' => 'Failed to update alert']);
    }
}

function getRiskAssessment() {
    global $conn, $doctor_id;
    
    $patient_id = (int)$_GET['patient_id'];
    
    // Check access
    $access_check = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM patient_access WHERE patient_id = $patient_id AND doctor_id = $doctor_id AND is_active = 1"
    ))['count'] ?? 0;
    
    if ($access_check == 0) {
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Get patient data
    $patient = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT * FROM patient WHERE patient_id = $patient_id"
    ));
    
    // Get latest vitals
    $vitals = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT * FROM vitals WHERE patient_id = $patient_id ORDER BY measured_at DESC LIMIT 1"
    ));
    
    // Get medical history
    $history_query = mysqli_query($conn, 
        "SELECT disease_name FROM patient_disease_history WHERE patient_id = $patient_id"
    );
    $medical_history = [];
    while ($row = mysqli_fetch_assoc($history_query)) {
        $medical_history[] = strtolower($row['disease_name']);
    }
    
    // Include risk engine
    include_once "../engine/risk_engine.php";
    $riskEngine = new RiskEngine();
    
    $patientData = [
        'age' => $patient['age'],
        'gender' => $patient['gender']
    ];
    
    $vitalsData = $vitals ? [
        'blood_pressure' => $vitals['blood_pressure'],
        'heart_rate' => $vitals['heart_rate'],
        'temperature' => $vitals['temperature'],
        'oxygen_saturation' => $vitals['oxygen_saturation']
    ] : [];
    
    $riskAssessment = $riskEngine->calculateRiskScore($patientData, $vitalsData, $medical_history);
    
    // Save risk assessment to database
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO patient_risk_scores (patient_id, doctor_id, risk_score, risk_factors) VALUES (?, ?, ?, ?)"
    );
    $riskFactorsJson = json_encode($riskAssessment['risk_factors']);
    mysqli_stmt_bind_param($stmt, "iids", $patient_id, $doctor_id, $riskAssessment['risk_score'], $riskFactorsJson);
    mysqli_stmt_execute($stmt);
    
    echo json_encode(['success' => true, 'risk_assessment' => $riskAssessment]);
}

function getAISummary() {
    global $conn, $doctor_id;
    
    $patient_id = (int)$_GET['patient_id'];
    
    // Check access
    $access_check = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM patient_access WHERE patient_id = $patient_id AND doctor_id = $doctor_id AND is_active = 1"
    ))['count'] ?? 0;
    
    if ($access_check == 0) {
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Get patient data
    $patient = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT * FROM patient WHERE patient_id = $patient_id"
    ));
    
    // Get vitals history
    $vitals_query = mysqli_query($conn, 
        "SELECT * FROM vitals WHERE patient_id = $patient_id ORDER BY measured_at DESC LIMIT 5"
    );
    $vitalsHistory = [];
    while ($row = mysqli_fetch_assoc($vitals_query)) {
        $vitalsHistory[] = $row;
    }
    
    // Get medical history
    $history_query = mysqli_query($conn, 
        "SELECT * FROM patient_disease_history WHERE patient_id = $patient_id ORDER BY detected_date DESC"
    );
    $medicalHistory = [];
    while ($row = mysqli_fetch_assoc($history_query)) {
        $medicalHistory[] = $row;
    }
    
    // Get doctor notes
    $notes_query = mysqli_query($conn, 
        "SELECT dn.*, dl.name as doctor_name 
         FROM doctor_notes dn 
         JOIN doctor_login dl ON dn.doctor_id = dl.doctor_id 
         WHERE dn.patient_id = $patient_id 
         ORDER BY dn.created_at DESC LIMIT 5"
    );
    $doctorNotes = [];
    while ($row = mysqli_fetch_assoc($notes_query)) {
        $doctorNotes[] = $row;
    }
    
    // Include AI summary engine
    include_once "../engine/ai_summary_engine_new.php";
    $aiEngine = new AISummaryEngine();
    
    $summary = $aiEngine->generatePatientSummary($patient, $vitalsHistory, $medicalHistory, $doctorNotes);
    
    echo json_encode(['success' => true, 'ai_summary' => $summary]);
}
?>
