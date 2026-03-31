<?php
session_start();
include "db.php";

// Get patient ID from URL or session
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 1; // Default to patient 1 for demo

// Get patient information
$patient = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM patient WHERE patient_id = $patient_id"));

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_report'])) {
        $patient_id = (int)$_POST['patient_id'];
        $report_type = trim($_POST['report_type']);
        $test_name = trim($_POST['test_name']);
        $test_date = $_POST['test_date'];
        $result_value = trim($_POST['result_value']);
        $normal_range = trim($_POST['normal_range']);
        $status = $_POST['status'];
        $notes = trim($_POST['notes']);
        $reported_by = trim($_POST['reported_by']);
        
        // Handle file upload
        $file_path = '';
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
            $upload_dir = 'uploads/patient_reports/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['report_file']['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['report_file']['tmp_name'], $file_path)) {
                // Store in lab_results table
                $stmt = mysqli_prepare($conn, 
                    "INSERT INTO lab_results (patient_id, doctor_id, test_name, test_type, result_value, normal_range, status, notes, test_date, reported_by) 
                     VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt, "issssssss", $patient_id, $test_name, $report_type, $result_value, $normal_range, $status, $notes, $test_date, $reported_by);
                mysqli_stmt_execute($stmt);
                
                $upload_success = "Report uploaded successfully!";
            }
        }
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
        
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO medications (patient_id, doctor_id, medication_name, dosage, frequency, route, start_date, end_date, purpose, prescribed_by) 
             VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        mysqli_stmt_bind_param($stmt, "isssssssi", $patient_id, $medication_name, $dosage, $frequency, $route, $start_date, $end_date, $purpose);
        mysqli_stmt_execute($stmt);
        
        $medication_success = "Medication added to your records!";
    }
    
    if (isset($_POST['add_medical_history'])) {
        $patient_id = (int)$_POST['patient_id'];
        $condition_name = trim($_POST['condition_name']);
        $diagnosis_date = $_POST['diagnosis_date'];
        $severity = $_POST['severity'];
        $description = trim($_POST['description']);
        $treatment_plan = trim($_POST['treatment_plan']);
        
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO medical_history (patient_id, doctor_id, condition_name, diagnosis_date, severity, description, treatment_plan) 
             VALUES (?, 1, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "issssss", $patient_id, $condition_name, $diagnosis_date, $severity, $description, $treatment_plan);
        mysqli_stmt_execute($stmt);
        
        $history_success = "Medical history added to your records!";
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
             VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        mysqli_stmt_bind_param($stmt, "iiiiiddiiis", $patient_id, $bp_systolic, $bp_diastolic, $heart_rate, $temperature, $weight, $height, $oxygen_saturation, $blood_sugar, $notes);
        mysqli_stmt_execute($stmt);
        
        $vitals_success = "Vitals recorded successfully!";
    }
}

// Get patient's medical data
$medical_history = mysqli_query($conn, 
    "SELECT mh.*, dl.name as doctor_name 
     FROM medical_history mh 
     LEFT JOIN doctor_login dl ON mh.doctor_id = dl.doctor_id 
     WHERE mh.patient_id = $patient_id 
     ORDER BY mh.diagnosis_date DESC"
);

$medications = mysqli_query($conn, 
    "SELECT m.*, dl.name as prescribing_doctor 
     FROM medications m 
     LEFT JOIN doctor_login dl ON m.prescribed_by = dl.doctor_id 
     WHERE m.patient_id = $patient_id 
     ORDER BY m.start_date DESC"
);

$vitals = mysqli_query($conn, 
    "SELECT v.*, dl.name as doctor_name 
     FROM vitals v 
     LEFT JOIN doctor_login dl ON v.created_by = dl.doctor_id 
     WHERE v.patient_id = $patient_id 
     ORDER BY v.measured_at DESC LIMIT 5"
);

$lab_results = mysqli_query($conn, 
    "SELECT lr.*, dl.name as doctor_name 
     FROM lab_results lr 
     LEFT JOIN doctor_login dl ON lr.doctor_id = dl.doctor_id 
     WHERE lr.patient_id = $patient_id 
     ORDER BY lr.test_date DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Upload Portal - HealthIntel</title>
    <link rel="stylesheet" href="assets/style-enhanced.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .patient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .upload-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .upload-form {
            background: #F8FAFF;
            border: 1px solid #E1E8ED;
            border-radius: 10px;
            padding: 20px;
        }
        .timeline-item {
            background: #F8FAFF;
            border: 1px solid #E1E8ED;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #4A90E2;
        }
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .timeline-title {
            font-weight: 600;
            color: #333;
        }
        .timeline-date {
            color: #666;
            font-size: 14px;
        }
        .success-message {
            background: #E8F5E8;
            color: #2D7A2D;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4CAF50;
        }
        .file-upload {
            border: 2px dashed #4A90E2;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #F8FAFF;
        }
        .file-upload input[type="file"] {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="patient-header">
            <div class="patient-info">
                <h1><?php echo htmlspecialchars($patient['name']); ?> - Upload Portal</h1>
                <div class="patient-meta">
                    <div class="meta-item">
                        <span>🆔</span>
                        <span>ID: <?php echo $patient['patient_id']; ?></span>
                    </div>
                    <div class="meta-item">
                        <span>🎂</span>
                        <span><?php echo $patient['dob'] ? (date('Y') - date('Y', strtotime($patient['dob'])) . ' years' : 'Age not specified'; ?></span>
                    </div>
                    <div class="meta-item">
                        <span>⚧</span>
                        <span><?php echo $patient['gender'] ?: 'Not specified'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($upload_success)): ?>
            <div class="success-message">
                ✅ <?php echo $upload_success; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($medication_success)): ?>
            <div class="success-message">
                ✅ <?php echo $medication_success; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($history_success)): ?>
            <div class="success-message">
                ✅ <?php echo $history_success; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($vitals_success)): ?>
            <div class="success-message">
                ✅ <?php echo $vitals_success; ?>
            </div>
        <?php endif; ?>

        <!-- Upload Medical Report -->
        <div class="upload-section">
            <div class="section-header">
                <h2 class="section-title">📄 Upload Medical Report</h2>
            </div>
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="report_type">Report Type *</label>
                        <select id="report_type" name="report_type" required>
                            <option value="">Select Type</option>
                            <option value="Blood Test">Blood Test</option>
                            <option value="X-Ray">X-Ray</option>
                            <option value="MRI">MRI</option>
                            <option value="CT Scan">CT Scan</option>
                            <option value="Ultrasound">Ultrasound</option>
                            <option value="ECG/EKG">ECG/EKG</option>
                            <option value="Pathology">Pathology</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="test_name">Test Name *</label>
                        <input type="text" id="test_name" name="test_name" placeholder="e.g., Complete Blood Count" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="test_date">Test Date *</label>
                        <input type="date" id="test_date" name="test_date" required>
                    </div>
                    <div class="form-group">
                        <label for="status">Result Status *</label>
                        <select id="status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="normal">Normal</option>
                            <option value="abnormal">Abnormal</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="result_value">Result Value *</label>
                        <input type="text" id="result_value" name="result_value" placeholder="e.g., 120/80 mmHg" required>
                    </div>
                    <div class="form-group">
                        <label for="normal_range">Normal Range</label>
                        <input type="text" id="normal_range" name="normal_range" placeholder="e.g., 90-120/60-80 mmHg">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reported_by">Reported By</label>
                    <input type="text" id="reported_by" name="reported_by" placeholder="Lab/Hospital Name">
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Additional notes about the test..."></textarea>
                </div>
                <div class="file-upload">
                    <p>📁 Upload Report File (PDF, JPG, PNG)</p>
                    <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" name="upload_report" class="btn-primary">📤 Upload Report</button>
                </div>
            </form>
        </div>

        <!-- Add Medication -->
        <div class="upload-section">
            <div class="section-header">
                <h2 class="section-title">💊 Add Current Medication</h2>
            </div>
            <form method="POST" class="upload-form">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
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
                    <textarea id="purpose" name="purpose" rows="2" placeholder="Reason for taking this medication..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_medication" class="btn-primary">➕ Add Medication</button>
                </div>
            </form>
        </div>

        <!-- Add Medical History -->
        <div class="upload-section">
            <div class="section-header">
                <h2 class="section-title">📋 Add Medical History</h2>
            </div>
            <form method="POST" class="upload-form">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
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
                </div>
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" rows="3" placeholder="Describe the condition..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="treatment_plan">Treatment Plan *</label>
                    <textarea id="treatment_plan" name="treatment_plan" rows="3" placeholder="Describe the treatment plan..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_medical_history" class="btn-primary">➕ Add Medical History</button>
                </div>
            </form>
        </div>

        <!-- Add Vitals -->
        <div class="upload-section">
            <div class="section-header">
                <h2 class="section-title">📊 Record Vitals</h2>
            </div>
            <form method="POST" class="upload-form">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="bp_systolic">BP Systolic</label>
                        <input type="number" id="bp_systolic" name="bp_systolic" placeholder="120">
                    </div>
                    <div class="form-group">
                        <label for="bp_diastolic">BP Diastolic</label>
                        <input type="number" id="bp_diastolic" name="bp_diastolic" placeholder="80">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="heart_rate">Heart Rate (bpm)</label>
                        <input type="number" id="heart_rate" name="heart_rate" placeholder="72">
                    </div>
                    <div class="form-group">
                        <label for="temperature">Temperature (°F)</label>
                        <input type="number" step="0.1" id="temperature" name="temperature" placeholder="98.6">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="weight">Weight (kg)</label>
                        <input type="number" step="0.1" id="weight" name="weight" placeholder="70">
                    </div>
                    <div class="form-group">
                        <label for="height">Height (cm)</label>
                        <input type="number" step="0.1" id="height" name="height" placeholder="170">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="oxygen_saturation">Oxygen Saturation (%)</label>
                        <input type="number" id="oxygen_saturation" name="oxygen_saturation" placeholder="98">
                    </div>
                    <div class="form-group">
                        <label for="blood_sugar">Blood Sugar (mg/dL)</label>
                        <input type="number" step="0.1" id="blood_sugar" name="blood_sugar" placeholder="100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="vitals_notes">Notes</label>
                    <textarea id="vitals_notes" name="vitals_notes" rows="2" placeholder="Additional notes..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_vitals" class="btn-primary">📊 Record Vitals</button>
                </div>
            </form>
        </div>

        <!-- Patient Timeline -->
        <div class="upload-section">
            <div class="section-header">
                <h2 class="section-title">📅 Your Medical Timeline</h2>
            </div>

            <?php if (mysqli_num_rows($lab_results) > 0): ?>
                <h3>📄 Medical Reports</h3>
                <?php while ($lab = mysqli_fetch_assoc($lab_results)): ?>
                    <div class="timeline-item">
                        <div class="timeline-header">
                            <div class="timeline-title"><?php echo htmlspecialchars($lab['test_name']); ?> - <?php echo htmlspecialchars($lab['test_type']); ?></div>
                            <div class="timeline-date"><?php echo date('M j, Y', strtotime($lab['test_date'])); ?></div>
                        </div>
                        <div>
                            <strong>Result:</strong> <?php echo htmlspecialchars($lab['result_value']); ?><br>
                            <strong>Status:</strong> <span class="status-<?php echo $lab['status']; ?>"><?php echo htmlspecialchars($lab['status']); ?></span><br>
                            <?php if ($lab['reported_by']): ?>
                                <strong>Reported by:</strong> <?php echo htmlspecialchars($lab['reported_by']; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php if (mysqli_num_rows($medications) > 0): ?>
                <h3>💊 Medications</h3>
                <?php while ($med = mysqli_fetch_assoc($medications)): ?>
                    <div class="timeline-item">
                        <div class="timeline-header">
                            <div class="timeline-title"><?php echo htmlspecialchars($med['medication_name']); ?> - <?php echo htmlspecialchars($med['dosage']); ?></div>
                            <div class="timeline-date">Started: <?php echo date('M j, Y', strtotime($med['start_date'])); ?></div>
                        </div>
                        <div>
                            <strong>Frequency:</strong> <?php echo htmlspecialchars($med['frequency']); ?><br>
                            <strong>Purpose:</strong> <?php echo htmlspecialchars($med['purpose']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php if (mysqli_num_rows($medical_history) > 0): ?>
                <h3>📋 Medical History</h3>
                <?php while ($history = mysqli_fetch_assoc($medical_history)): ?>
                    <div class="timeline-item">
                        <div class="timeline-header">
                            <div class="timeline-title"><?php echo htmlspecialchars($history['condition_name']); ?></div>
                            <div class="timeline-date"><?php echo date('M j, Y', strtotime($history['diagnosis_date'])); ?></div>
                        </div>
                        <div>
                            <strong>Severity:</strong> <?php echo htmlspecialchars($history['severity']); ?><br>
                            <strong>Description:</strong> <?php echo htmlspecialchars($history['description']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php if (mysqli_num_rows($vitals) > 0): ?>
                <h3>📊 Vitals</h3>
                <?php while ($vital = mysqli_fetch_assoc($vitals)): ?>
                    <div class="timeline-item">
                        <div class="timeline-header">
                            <div class="timeline-title">Vital Signs</div>
                            <div class="timeline-date"><?php echo date('M j, Y H:i', strtotime($vital['measured_at'])); ?></div>
                        </div>
                        <div>
                            <?php if ($vital['blood_pressure_systolic'] && $vital['blood_pressure_diastolic']): ?>
                                <strong>BP:</strong> <?php echo $vital['blood_pressure_systolic']; ?>/<?php echo $vital['blood_pressure_diastolic']; ?> mmHg<br>
                            <?php endif; ?>
                            <?php if ($vital['heart_rate']): ?>
                                <strong>Heart Rate:</strong> <?php echo $vital['heart_rate']; ?> bpm<br>
                            <?php endif; ?>
                            <?php if ($vital['temperature']): ?>
                                <strong>Temperature:</strong> <?php echo $vital['temperature']; ?>°F<br>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <?php if (mysqli_num_rows($lab_results) == 0 && mysqli_num_rows($medications) == 0 && mysqli_num_rows($medical_history) == 0 && mysqli_num_rows($vitals) == 0): ?>
                <p style="text-align: center; color: #666; padding: 40px;">No medical data uploaded yet. Start by uploading your reports and adding your medical information above.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
