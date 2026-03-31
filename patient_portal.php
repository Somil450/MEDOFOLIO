<?php
session_start();
include "db.php";

// Check if patient is logged in (you would implement patient login)
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 1; // Default to patient 1 for demo

// Get patient information
$patient = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM patient WHERE patient_id = $patient_id"));

// Get medical history (from both medical_history and doctor_medical_history tables)
$medical_history = mysqli_query($conn, 
    "SELECT 
        mh.id,
        mh.patient_id,
        mh.doctor_id,
        mh.condition_name,
        mh.diagnosis_date,
        mh.severity,
        mh.status,
        mh.description,
        mh.treatment_plan,
        mh.file_name,
        mh.file_path,
        mh.follow_up_required,
        mh.follow_up_date,
        dl.name as doctor_name,
        dl.specialization,
        'doctor_medical_history' as source_table,
        'Doctor Added' as source
     FROM doctor_medical_history mh 
     JOIN doctor_login dl ON mh.doctor_id = dl.doctor_id 
     WHERE mh.patient_id = $patient_id
     
     UNION ALL
     
     SELECT 
        pdh.history_id as id,
        pdh.patient_id,
        NULL as doctor_id,
        pdh.disease_id as condition_name,
        pdh.detected_date as diagnosis_date,
        pdh.severity_level as severity,
        pdh.status,
        pdh.notes as description,
        NULL as treatment_plan,
        NULL as file_name,
        NULL as file_path,
        NULL as follow_up_required,
        NULL as follow_up_date,
        'Medofolio System' as doctor_name,
        'System' as specialization,
        'patient_disease_history' as source_table,
        'Medofolio Patient' as source
     FROM patient_disease_history pdh 
     WHERE pdh.patient_id = $patient_id
     
     ORDER BY diagnosis_date DESC"
);

// Get medications
$medications = mysqli_query($conn, 
    "SELECT m.*, dl.name as prescribing_doctor 
     FROM medications m 
     JOIN doctor_login dl ON m.prescribed_by = dl.doctor_id 
     WHERE m.patient_id = $patient_id AND m.status = 'active'
     ORDER BY m.start_date DESC"
);

// Get vitals
$vitals = mysqli_query($conn, 
    "SELECT v.*, dl.name as doctor_name 
     FROM vitals v 
     JOIN doctor_login dl ON v.created_by = dl.doctor_id 
     WHERE v.patient_id = $patient_id 
     ORDER BY v.measured_at DESC LIMIT 5"
);

// Get allergies
$allergies = mysqli_query($conn, 
    "SELECT a.*, dl.name as doctor_name 
     FROM allergies a 
     JOIN doctor_login dl ON a.doctor_id = dl.doctor_id 
     WHERE a.patient_id = $patient_id AND a.status = 'active'
     ORDER BY a.severity DESC"
);

// Get lab results
$lab_results = mysqli_query($conn, 
    "SELECT lr.*, dl.name as doctor_name 
     FROM lab_results lr 
     JOIN doctor_login dl ON lr.doctor_id = dl.doctor_id 
     WHERE lr.patient_id = $patient_id 
     ORDER BY lr.test_date DESC LIMIT 10"
);

// Get doctor notes
$doctor_notes = mysqli_query($conn, 
    "SELECT dn.*, dl.name as doctor_name, dl.specialization 
     FROM doctor_notes dn 
     JOIN doctor_login dl ON dn.doctor_id = dl.doctor_id 
     WHERE dn.patient_id = $patient_id 
     ORDER BY dn.created_at DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - HealthIntel</title>
    <link rel="stylesheet" href="assets/premium-medical.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-medical: #2563eb;
            --medical-blue: #3b82f6;
            --medical-green: #10b981;
            --medical-red: #ef4444;
            --medical-orange: #f59e0b;
            --medical-purple: #8b5cf6;
            --medical-gray: #6b7280;
            --medical-light: #f8fafc;
            --medical-dark: #1e293b;
            --gradient-medical: linear-gradient(135deg, var(--primary-medical), var(--medical-blue));
            --gradient-success: linear-gradient(135deg, #10b981, #059669);
            --gradient-warning: linear-gradient(135deg, #f59e0b, #d97706);
            --gradient-danger: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--medical-dark);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .patient-header {
            background: var(--gradient-medical);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .patient-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(180deg); }
        }
        
        .patient-info {
            position: relative;
            z-index: 1;
        }
        
        .patient-info h1 {
            margin: 0 0 15px 0;
            font-size: 32px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .patient-meta {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.15);
            padding: 8px 16px;
            border-radius: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .section:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--medical-light);
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--medical-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: var(--gradient-medical);
            border-radius: 2px;
        }
        
        .update-badge {
            background: var(--gradient-success);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .medical-card {
            background: var(--medical-light);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-medical);
            transition: all 0.3s ease;
        }
        
        .medical-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.1);
        }
        
        .medical-card h4 {
            color: var(--primary-medical);
            margin: 0 0 10px 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .medical-card p {
            margin: 0;
            color: var(--medical-gray);
            line-height: 1.5;
        }
        
        .medical-date {
            font-size: 14px;
            color: var(--medical-gray);
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: var(--gradient-medical);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }
        
        .btn-secondary {
            background: var(--gradient-warning);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: var(--gradient-success);
            color: white;
        }
        
        .status-pending {
            background: var(--gradient-warning);
            color: white;
        }
        
        .status-inactive {
            background: var(--gradient-danger);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--medical-light);
            border-radius: 20px;
            border: 2px dashed var(--medical-gray);
        }
        
        .empty-state h3 {
            color: var(--medical-gray);
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .empty-state p {
            color: var(--medical-gray);
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .patient-header {
                padding: 25px;
            }
            
            .patient-info h1 {
                font-size: 24px;
            }
            
            .section {
                padding: 20px;
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .patient-meta {
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="patient-header">
            <div class="patient-info">
                <h1><?php echo htmlspecialchars($patient['name']); ?></h1>
                <div class="patient-meta">
                    <div class="meta-item">
                        <span>📅</span>
                        <span>Age: <?php echo date('Y') - date('Y', strtotime($patient['dob'])); ?> years</span>
                    </div>
                    <div class="meta-item">
                        <span>🩺</span>
                        <span><?php echo htmlspecialchars($patient['gender']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>📍</span>
                        <span><?php echo htmlspecialchars($patient['region']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>🩸</span>
                        <span><?php echo htmlspecialchars($patient['blood_group']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Recent Updates -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Doctor Updates</h2>
                <span class="update-badge">Live Updates</span>
            </div>
            <?php if (mysqli_num_rows($doctor_notes) > 0): ?>
                <?php while ($note = mysqli_fetch_assoc($doctor_notes)): ?>
                    <div class="medical-card">
                        <h4>🩺 Doctor Note</h4>
                        <div class="medical-date">
                            <span>📅</span>
                            <?php echo date('M j, Y H:i', strtotime($note['created_at'])); ?>
                        </div>
                        <p>
                            <strong>Diagnosis:</strong> <?php echo htmlspecialchars($note['diagnosis']); ?><br>
                            <strong>Prescription:</strong> <?php echo htmlspecialchars($note['prescription']); ?>
                            <?php if ($note['follow_up_date']): ?>
                                <br><strong>Follow-up:</strong> <?php echo date('M j, Y', strtotime($note['follow_up_date'])); ?>
                            <?php endif; ?>
                        </p>
                        <div class="medical-date">
                            <span>🩺</span>
                            Dr. <?php echo htmlspecialchars($note['doctor_name']); ?> (<?php echo htmlspecialchars($note['specialization']); ?>)
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No recent updates from your doctor.</p>
            <?php endif; ?>
        </div>

        <!-- Medical History -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Medical History</h2>
            </div>
            <?php if (mysqli_num_rows($medical_history) > 0): ?>
                <?php while ($history = mysqli_fetch_assoc($medical_history)): ?>
                    <div class="medical-item">
                        <div class="medical-item-header">
                            <div class="medical-item-title"><?php echo htmlspecialchars($history['condition_name']); ?></div>
                            <div class="medical-item-date"><?php echo date('M j, Y', strtotime($history['diagnosis_date'])); ?></div>
                        </div>
                        <div class="medical-item-content">
                            <strong>Severity:</strong> <?php echo htmlspecialchars($history['severity']); ?><br>
                            <strong>Description:</strong> <?php echo htmlspecialchars($history['description']); ?><br>
                            <strong>Treatment:</strong> <?php echo htmlspecialchars($history['treatment_plan']); ?>
                            <?php if ($history['file_name']): ?>
                                <br><strong>Medical File:</strong> <a href="<?php echo htmlspecialchars($history['file_path']); ?>" target="_blank" style="color: #4A90E2; text-decoration: none;">📎 <?php echo htmlspecialchars($history['file_name']); ?></a>
                            <?php endif; ?>
                            <?php if ($history['follow_up_required']): ?>
                                <br><strong>Follow-up:</strong> <?php echo $history['follow_up_date'] ? date('M j, Y', strtotime($history['follow_up_date'])) : 'Required'; ?>
                            <?php endif; ?>
                        </div>
                        <div class="doctor-info">
                            🩺 Dr. <?php echo htmlspecialchars($history['doctor_name']); ?> (<?php echo htmlspecialchars($history['specialization']); ?>)
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No medical history recorded.</p>
            <?php endif; ?>
        </div>

        <!-- Current Medications -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Current Medications</h2>
            </div>
            <?php if (mysqli_num_rows($medications) > 0): ?>
                <?php while ($med = mysqli_fetch_assoc($medications)): ?>
                    <div class="medical-item">
                        <div class="medical-item-header">
                            <div class="medical-item-title"><?php echo htmlspecialchars($med['medication_name']); ?></div>
                            <div class="medical-item-date">Started: <?php echo date('M j, Y', strtotime($med['start_date'])); ?></div>
                        </div>
                        <div class="medical-item-content">
                            <strong>Dosage:</strong> <?php echo htmlspecialchars($med['dosage']); ?><br>
                            <strong>Frequency:</strong> <?php echo htmlspecialchars($med['frequency']); ?><br>
                            <strong>Route:</strong> <?php echo htmlspecialchars($med['route']); ?><br>
                            <strong>Purpose:</strong> <?php echo htmlspecialchars($med['purpose']); ?>
                            <?php if ($med['end_date']): ?>
                                <br><strong>End Date:</strong> <?php echo date('M j, Y', strtotime($med['end_date'])); ?>
                            <?php endif; ?>
                        </div>
                        <div class="doctor-info">
                            💊 Prescribed by Dr. <?php echo htmlspecialchars($med['prescribing_doctor']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No active medications.</p>
            <?php endif; ?>
        </div>

        <!-- Allergies -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Allergies</h2>
            </div>
            <?php if (mysqli_num_rows($allergies) > 0): ?>
                <?php while ($allergy = mysqli_fetch_assoc($allergies)): ?>
                    <div class="medical-item <?php echo $allergy['severity'] == 'life_threatening' ? 'alert-critical' : ($allergy['severity'] == 'severe' ? 'alert-severe' : ''); ?>">
                        <div class="medical-item-header">
                            <div class="medical-item-title"><?php echo htmlspecialchars($allergy['allergen']); ?> (<?php echo htmlspecialchars($allergy['allergy_type']); ?>)</div>
                            <div class="medical-item-date">Diagnosed: <?php echo date('M j, Y', strtotime($allergy['diagnosed_date'])); ?></div>
                        </div>
                        <div class="medical-item-content">
                            <strong>Severity:</strong> <?php echo htmlspecialchars($allergy['severity']); ?><br>
                            <strong>Reaction:</strong> <?php echo htmlspecialchars($allergy['reaction']); ?>
                            <?php if ($allergy['notes']): ?>
                                <br><strong>Notes:</strong> <?php echo htmlspecialchars($allergy['notes']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="doctor-info">
                            ⚠️ Recorded by Dr. <?php echo htmlspecialchars($allergy['doctor_name']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No allergies recorded.</p>
            <?php endif; ?>
        </div>

        <!-- Latest Vitals -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Latest Vitals</h2>
            </div>
            <?php if (mysqli_num_rows($vitals) > 0): ?>
                <?php $latest_vital = mysqli_fetch_assoc($vitals); ?>
                <div class="vital-grid">
                    <?php if ($latest_vital['blood_pressure_systolic'] && $latest_vital['blood_pressure_diastolic']): ?>
                        <div class="vital-card">
                            <div class="vital-value"><?php echo $latest_vital['blood_pressure_systolic']; ?>/<?php echo $latest_vital['blood_pressure_diastolic']; ?></div>
                            <div class="vital-label">Blood Pressure</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($latest_vital['heart_rate']): ?>
                        <div class="vital-card">
                            <div class="vital-value"><?php echo $latest_vital['heart_rate']; ?></div>
                            <div class="vital-label">Heart Rate</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($latest_vital['temperature']): ?>
                        <div class="vital-card">
                            <div class="vital-value"><?php echo $latest_vital['temperature']; ?>°F</div>
                            <div class="vital-label">Temperature</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($latest_vital['weight']): ?>
                        <div class="vital-card">
                            <div class="vital-value"><?php echo $latest_vital['weight']; ?> kg</div>
                            <div class="vital-label">Weight</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($latest_vital['oxygen_saturation']): ?>
                        <div class="vital-card">
                            <div class="vital-value"><?php echo $latest_vital['oxygen_saturation']; ?>%</div>
                            <div class="vital-label">Oxygen</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="doctor-info">
                    📊 Recorded on <?php echo date('M j, Y H:i', strtotime($latest_vital['measured_at'])); ?> by Dr. <?php echo htmlspecialchars($latest_vital['doctor_name']); ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No vitals recorded.</p>
            <?php endif; ?>
        </div>

        <!-- Lab Results -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Lab Results</h2>
            </div>
            <?php if (mysqli_num_rows($lab_results) > 0): ?>
                <?php while ($lab = mysqli_fetch_assoc($lab_results)): ?>
                    <div class="medical-item">
                        <div class="medical-item-header">
                            <div class="medical-item-title"><?php echo htmlspecialchars($lab['test_name']); ?></div>
                            <div class="medical-item-date"><?php echo date('M j, Y', strtotime($lab['test_date'])); ?></div>
                        </div>
                        <div class="medical-item-content">
                            <strong>Result:</strong> <?php echo htmlspecialchars($lab['result_value']); ?><br>
                            <strong>Normal Range:</strong> <?php echo htmlspecialchars($lab['normal_range']); ?><br>
                            <strong>Status:</strong> <span class="status-<?php echo $lab['status']; ?>"><?php echo htmlspecialchars($lab['status']); ?></span>
                            <?php if ($lab['notes']): ?>
                                <br><strong>Notes:</strong> <?php echo htmlspecialchars($lab['notes']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="doctor-info">
                            🧪 Reported by <?php echo htmlspecialchars($lab['reported_by']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No lab results available.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
