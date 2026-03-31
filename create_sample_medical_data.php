<?php
include "db.php";

echo "=== Creating Sample Medical Data ===\n";

// Get some real patients and doctors
$patients = mysqli_query($conn, "SELECT patient_id FROM patient LIMIT 5");
$doctors = mysqli_query($conn, "SELECT doctor_id FROM doctor_login LIMIT 3");

$patient_ids = [];
while ($row = mysqli_fetch_assoc($patients)) {
    $patient_ids[] = $row['patient_id'];
}

$doctor_ids = [];
while ($row = mysqli_fetch_assoc($doctors)) {
    $doctor_ids[] = $row['doctor_id'];
}

// Sample medical history
$medical_histories = [
    ['condition' => 'Hypertension', 'severity' => 'moderate', 'description' => 'Chronic high blood pressure', 'treatment' => 'Lifestyle modifications and medication'],
    ['condition' => 'Type 2 Diabetes', 'severity' => 'moderate', 'description' => 'Insulin resistance', 'treatment' => 'Metformin and diet control'],
    ['condition' => 'Asthma', 'severity' => 'mild', 'description' => 'Seasonal allergic asthma', 'treatment' => 'Inhaler as needed'],
    ['condition' => 'Coronary Artery Disease', 'severity' => 'severe', 'description' => 'Blockage in coronary arteries', 'treatment' => 'Stents and medication'],
    ['condition' => 'Osteoarthritis', 'severity' => 'moderate', 'description' => 'Joint degeneration', 'treatment' => 'Physical therapy and pain management']
];

foreach ($patient_ids as $index => $patient_id) {
    if ($index < count($medical_histories)) {
        $history = $medical_histories[$index];
        $doctor_id = $doctor_ids[$index % count($doctor_ids)];
        
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO medical_history (patient_id, doctor_id, condition_name, diagnosis_date, severity, status, description, treatment_plan) 
             VALUES (?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? MONTH), ?, 'active', ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iisssss", $patient_id, $doctor_id, $history['condition'], 
            rand(1, 24), $history['severity'], $history['description'], $history['treatment']);
        mysqli_stmt_execute($stmt);
    }
}

// Sample medications
$medications = [
    ['name' => 'Lisinopril', 'dosage' => '10mg', 'frequency' => 'Once daily', 'purpose' => 'Blood pressure control'],
    ['name' => 'Metformin', 'dosage' => '500mg', 'frequency' => 'Twice daily', 'purpose' => 'Blood sugar control'],
    ['name' => 'Albuterol', 'dosage' => '90mcg', 'frequency' => 'As needed', 'purpose' => 'Asthma relief'],
    ['name' => 'Aspirin', 'dosage' => '81mg', 'frequency' => 'Once daily', 'purpose' => 'Blood thinning'],
    ['name' => 'Ibuprofen', 'dosage' => '400mg', 'frequency' => 'As needed', 'purpose' => 'Pain relief']
];

foreach ($patient_ids as $index => $patient_id) {
    if ($index < count($medications)) {
        $med = $medications[$index];
        $doctor_id = $doctor_ids[$index % count($doctor_ids)];
        
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO medications (patient_id, doctor_id, medication_name, dosage, frequency, start_date, status, purpose, prescribed_by) 
             VALUES (?, ?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), 'active', ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iisssssi", $patient_id, $doctor_id, $med['name'], 
            $med['dosage'], $med['frequency'], rand(1, 30), $med['purpose'], $doctor_id);
        mysqli_stmt_execute($stmt);
    }
}

// Sample vitals
$vital_ranges = [
    ['bp_sys' => 120, 'bp_dia' => 80, 'hr' => 72, 'temp' => 98.6, 'oxygen' => 98],
    ['bp_sys' => 135, 'bp_dia' => 85, 'hr' => 78, 'temp' => 98.4, 'oxygen' => 97],
    ['bp_sys' => 140, 'bp_dia' => 90, 'hr' => 82, 'temp' => 99.1, 'oxygen' => 96],
    ['bp_sys' => 125, 'bp_dia' => 82, 'hr' => 75, 'temp' => 98.8, 'oxygen' => 98],
    ['bp_sys' => 130, 'bp_dia' => 84, 'hr' => 70, 'temp' => 98.2, 'oxygen' => 99]
];

foreach ($patient_ids as $index => $patient_id) {
    if ($index < count($vital_ranges)) {
        $vital = $vital_ranges[$index];
        $doctor_id = $doctor_ids[$index % count($doctor_ids)];
        
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO vitals (patient_id, doctor_id, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, temperature, oxygen_saturation, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "iiiiiddi", $patient_id, $doctor_id, 
            $vital['bp_sys'], $vital['bp_dia'], $vital['hr'], $vital['temp'], $vital['oxygen'], $doctor_id);
        mysqli_stmt_execute($stmt);
    }
}

// Sample allergies
$allergies = [
    ['allergen' => 'Penicillin', 'type' => 'drug', 'severity' => 'moderate', 'reaction' => 'Skin rash, itching'],
    ['allergen' => 'Pollen', 'type' => 'environmental', 'severity' => 'mild', 'reaction' => 'Sneezing, watery eyes'],
    ['allergen' => 'Shellfish', 'type' => 'food', 'severity' => 'severe', 'reaction' => 'Anaphylaxis'],
    ['allergen' => 'Dust mites', 'type' => 'environmental', 'severity' => 'mild', 'reaction' => 'Nasal congestion'],
    ['allergen' => 'Latex', 'type' => 'drug', 'severity' => 'moderate', 'reaction' => 'Contact dermatitis']
];

foreach ($patient_ids as $index => $patient_id) {
    if ($index < count($allergies)) {
        $allergy = $allergies[$index];
        $doctor_id = $doctor_ids[$index % count($doctor_ids)];
        
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO allergies (patient_id, doctor_id, allergen, allergy_type, severity, reaction, diagnosed_date) 
             VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? YEAR))"
        );
        mysqli_stmt_bind_param($stmt, "iissssi", $patient_id, $doctor_id, 
            $allergy['allergen'], $allergy['type'], $allergy['severity'], $allergy['reaction'], rand(1, 10));
        mysqli_stmt_execute($stmt);
    }
}

// Sample lab results
$lab_results = [
    ['test' => 'Complete Blood Count', 'type' => 'Hematology', 'value' => 'Normal range', 'status' => 'normal'],
    ['test' => 'Lipid Panel', 'type' => 'Chemistry', 'value' => 'LDL: 145 mg/dL', 'status' => 'abnormal'],
    ['test' => 'HbA1c', 'type' => 'Chemistry', 'value' => '7.2%', 'status' => 'abnormal'],
    ['test' => 'Thyroid Panel', 'type' => 'Endocrinology', 'value' => 'Normal range', 'status' => 'normal'],
    ['test' => 'Liver Function', 'type' => 'Chemistry', 'value' => 'Slightly elevated AST', 'status' => 'abnormal']
];

foreach ($patient_ids as $index => $patient_id) {
    if ($index < count($lab_results)) {
        $lab = $lab_results[$index];
        $doctor_id = $doctor_ids[$index % count($doctor_ids)];
        
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO lab_results (patient_id, doctor_id, test_name, test_type, result_value, status, test_date) 
             VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY))"
        );
        mysqli_stmt_bind_param($stmt, "iissssi", $patient_id, $doctor_id, 
            $lab['test'], $lab['type'], $lab['value'], $lab['status'], rand(1, 14));
        mysqli_stmt_execute($stmt);
    }
}

echo "✅ Sample medical data created successfully\n";
echo "✅ Medical history, medications, vitals, allergies, and lab results added\n";
?>
