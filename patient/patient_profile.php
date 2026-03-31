<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
include "../db.php";

// Handle payment success/error messages
$success_message = isset($_SESSION['payment_success']) ? $_SESSION['payment_success'] : '';
$error_message = isset($_SESSION['payment_error']) ? $_SESSION['payment_error'] : '';

// Clear session messages after displaying
unset($_SESSION['payment_success']);
unset($_SESSION['payment_error']);

include "../engine/risk_engine.php";
include "../engine/disease_engine.php";
include "../engine/ai_summary_engine.php";
$patient_id = (int) $_SESSION['user_id'];

/* ================= DISEASE MAP ================= */
function diseaseMap($disease) {
    $d = strtolower($disease);
    if (strpos($d,'asthma')!==false) return ['Pulmonologist','Chest Hospital'];
    if (strpos($d,'heart')!==false) return ['Cardiologist','Cardiac Hospital'];
    if (strpos($d,'hypertension')!==false) return ['Cardiologist','Heart Clinic'];
    if (strpos($d,'cancer')!==false) return ['Oncologist','Cancer Hospital'];
    if (strpos($d,'covid')!==false) return ['General Physician','Multi-specialty Hospital'];
    return ['General Physician','Hospital'];
}
/* ================= PATIENT ================= */
$patient = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM patient WHERE patient_id=$patient_id")
);
/* ================= AI SUMMARY ================= */
$historyData = [];
$hq = mysqli_query($conn,"
    SELECT d.disease_name, h.status, h.severity_level
    FROM patient_disease_history h
    JOIN disease_master d ON h.disease_id=d.disease_id
    WHERE h.patient_id=$patient_id
");
while ($r = mysqli_fetch_assoc($hq)) $historyData[] = $r;
$aiSummary = generateHealthSummary($historyData, $patient['region']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Patient Health Timeline - MedoFolio</title>
<link rel="stylesheet" href="../assets/style-enhanced.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .patient-header {
        background: linear-gradient(135deg, var(--primary-medical), var(--medical-blue));
        color: white;
        padding: 40px;
        border-radius: 20px;
        margin-bottom: 32px;
        position: relative;
        overflow: hidden;
    }
    
    .patient-header::before {
        content: "";
        position: absolute;
        top: -30%;
        right: -10%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }
    
    .patient-info {
        display: flex;
        align-items: center;
        gap: 24px;
        position: relative;
        z-index: 1;
    }
    
    .patient-avatar {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    .patient-details h1 {
        color: white;
        margin-bottom: 8px;
        font-size: 32px;
    }
    
    .patient-details p {
        opacity: 0.9;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .ai-summary-card {
        background: linear-gradient(135deg, rgba(0, 102, 204, 0.05), rgba(0, 120, 212, 0.02));
        border: 2px solid rgba(0, 102, 204, 0.1);
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 32px;
        position: relative;
    }
    
    .ai-summary-card::before {
        content: "🧠";
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 32px;
        opacity: 0.3;
    }
    
    .ai-summary-card h2 {
        color: var(--primary-medical);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }
    
    .timeline-header h2 {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
    }
    
    .medical-event {
        background: white;
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 24px;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0, 102, 204, 0.08);
        position: relative;
        transition: all 0.3s ease;
    }
    
    .medical-event:hover {
        transform: translateY(-2px);
        box-shadow: var(--hover-shadow);
    }
    
    .medical-event::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 6px;
        border-radius: 16px 0 0 16px;
    }
    
    .medical-event.critical::before {
        background: linear-gradient(135deg, var(--danger-red), #A4262C);
    }
    
    .medical-event.active::before {
        background: linear-gradient(135deg, var(--warning-amber), #E67E00);
    }
    
    .medical-event.recovered::before {
        background: linear-gradient(135deg, var(--health-green), #0E6B0E);
    }
    
    .medical-event.default::before {
        background: linear-gradient(135deg, var(--primary-medical), var(--medical-blue));
    }
    
    .event-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }
    
    .disease-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary-medical);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .disease-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--primary-medical), var(--medical-blue));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-badge.critical {
        background: rgba(209, 52, 56, 0.1);
        color: var(--danger-red);
    }
    
    .status-badge.active {
        background: rgba(255, 140, 0, 0.1);
        color: var(--warning-amber);
    }
    
    .status-badge.recovered {
        background: rgba(16, 124, 16, 0.1);
        color: var(--health-green);
    }
    
    .medical-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .detail-item {
        padding: 16px;
        background: var(--light-gray);
        border-radius: 12px;
        border: 1px solid rgba(0, 102, 204, 0.05);
    }
    
    .detail-label {
        font-size: 12px;
        color: var(--neutral-gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
        font-weight: 600;
    }
    
    .detail-value {
        font-size: 16px;
        font-weight: 600;
        color: var(--primary-medical);
    }
    
    .medical-actions {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #E1DFDD;
    }
    
    .action-group {
        flex: 1;
        min-width: 250px;
    }
    
    .action-group h4 {
        font-size: 14px;
        color: var(--neutral-gray);
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .appointment-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        align-items: end;
    }
    
    .upload-section {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .patient-info {
            flex-direction: column;
            text-align: center;
        }
        
        .patient-details h1 {
            font-size: 24px;
        }
        
        .event-header {
            flex-direction: column;
            gap: 16px;
        }
        
        .medical-details {
            grid-template-columns: 1fr;
        }
        
        .medical-actions {
            flex-direction: column;
        }
        
        .action-group {
            min-width: 100%;
        }
    }
</style>
</head>
<body>
<!-- PATIENT HEADER -->
<div class="patient-header amazing-header">
    <div class="patient-info">
        <div class="patient-avatar amazing-icon">👤</div>
        <div class="patient-details">
            <h1 class="golden-heading" data-text="<?= htmlspecialchars($patient['name']) ?>"><?= htmlspecialchars($patient['name']) ?></h1>
            <p style="font-size: 18px; opacity: 0.95; background: rgba(255,255,255,0.2); padding: 12px 20px; border-radius: 20px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.3);">📍 <?= htmlspecialchars($patient['region']) ?></p>
            <p style="font-size: 16px; opacity: 0.9; background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: 16px; backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2);">🆔 Patient ID: #<?= str_pad($patient_id, 6, '0', STR_PAD_LEFT) ?></p>
        </div>
    </div>
</div>

<!-- AI HEALTH SUMMARY -->
<div class="ai-summary-card amazing-card amazing-glow">
    <h2 class="amazing-text" data-text="🧠 MedoFolio AI Health Analysis" style="font-size: 24px;">🧠 MedoFolio AI Health Analysis</h2>
    <?= $aiSummary ?>
</div>
<!-- MEDICAL TIMELINE -->
<div class="timeline-header">
    <h2 class="amazing-text" data-text="Medical History Timeline" style="font-size: 24px; text-transform: uppercase; letter-spacing: 2px;">
        <div class="medical-icon amazing-icon">📋</div>
        Medical History Timeline
    </h2>
    <a href="../dashboard/dashboard.php" class="btn small amazing-button">← Back to Dashboard</a>
</div>

<div class="timeline">
<?php
// Combined query for both patient and doctor medical history
$q = mysqli_query($conn,"
    SELECT 
        d.disease_name, 
        h.detected_date, 
        h.status, 
        h.severity_level as severity,
        'Patient' as source
    FROM patient_disease_history h
    JOIN disease_master d ON h.disease_id=d.disease_id
    WHERE h.patient_id=$patient_id
    
    UNION ALL
    
    SELECT 
        d.disease_name, 
        h.diagnosis_date as detected_date, 
        h.status, 
        h.severity as severity,
        'Doctor' as source
    FROM doctor_medical_history h
    LEFT JOIN disease_master d ON h.condition_name = d.disease_name
    WHERE h.patient_id=$patient_id
    
    ORDER BY detected_date DESC
");
while ($row = mysqli_fetch_assoc($q)):
    [$specialist,$hospitalType] = diseaseMap($row['disease_name']);
    $risk = calculateRisk($row['severity'],$row['status']);
    $action = nextAction($risk);
?>
<div class="medical-event <?= strtolower($row['status']) ?> <?= !in_array(strtolower($row['status']), ['critical', 'active', 'recovered']) ? 'default' : '' ?> amazing-card">
    <div class="event-header">
        <div>
            <div class="disease-title">
                <div class="disease-icon amazing-icon">🏥</div>
                <?= htmlspecialchars($row['disease_name']) ?>
                <?php if ($row['source'] == 'Doctor'): ?>
                    <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; margin-left: 8px;">Doctor Added</span>
                <?php endif; ?>
            </div>
            <span class="status-badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span>
        </div>
    </div>
    
    <div class="medical-details">
        <div class="detail-item">
            <div class="detail-label">Detection Date</div>
            <div class="detail-value"><?= $row['detected_date'] ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Severity Level</div>
            <div class="detail-value"><?= $row['severity'] ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Risk Assessment</div>
            <div class="detail-value"><?= $risk ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Recommended Action</div>
            <div class="detail-value"><?= $action ?></div>
        </div>
    </div>
    
    <div class="medical-details">
        <div class="detail-item">
            <div class="detail-label">Specialist</div>
            <div class="detail-value"><?= $specialist ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Hospital Type</div>
            <div class="detail-value"><?= $hospitalType ?></div>
        </div>
    </div>
    <div class="medical-actions">
        <div class="action-group">
            <h4>📍 Find Healthcare</h4>
            <button class="btn full amazing-button" onclick="showMap('<?= htmlspecialchars($hospitalType) ?>', this)">
                🗺️ Locate Nearby <?= htmlspecialchars($hospitalType) ?>
            </button>
            <div class="mapBox" id="mapBox_<?= $row['disease_name'] ?>" style="margin-top:12px; display: none; min-height: 200px; background: #f8f9fa; border-radius: 10px; padding: 20px;"></div>
        </div>
        
        <div class="action-group">
            <h4>📄 Medical Reports</h4>
            <form method="POST" action="upload_report.php"
                  enctype="multipart/form-data" class="upload-section">
                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                <input type="hidden" name="disease_name"
                       value="<?= htmlspecialchars($row['disease_name']) ?>">
                <input type="file" name="report_file" required style="flex: 1; min-width: 200px;">
                <button class="btn amazing-button" type="submit">📤 Upload</button>
                <button type="button" class="btn secondary amazing-button" onclick="openReportsModal()">📂 View All</button>
            </form>
        </div>
        
        <div class="action-group">
            <h4>📅 Book Appointment</h4>
            <form method="POST" action="book_appointment.php" class="appointment-form">
                <input type="hidden" name="disease" value="<?= htmlspecialchars($row['disease_name']) ?>">
                <input type="hidden" name="specialty" value="<?= htmlspecialchars($specialist) ?>">
                <input type="hidden" name="hospital_type" value="<?= htmlspecialchars($hospitalType) ?>">
                <input type="date" name="date" required placeholder="Date">
                <input type="time" name="time" required placeholder="Time">
                <button class="btn amazing-button" type="submit">📅 Book</button>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>

<!-- PAYMENT INFORMATION -->
<div class="timeline-header">
    <h2 class="amazing-text" data-text="Payment Information" style="font-size: 24px; text-transform: uppercase; letter-spacing: 2px;">
        <div class="medical-icon amazing-icon">💳</div>
        Payment Information
    </h2>
</div>

<?php if (!empty($success_message)): ?>
<div class="ai-summary-card amazing-card amazing-glow" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(34, 197, 94, 0.1)); border: 2px solid #28a745;">
    <h3 style="color: #28a745; text-align: center; margin-bottom: 15px;">✅ Payment Successful!</h3>
    <p style="text-align: center; color: #155724; font-weight: 600;"><?= htmlspecialchars($success_message) ?></p>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="medical-event amazing-card" style="border: 2px solid #dc3545; background: #fee8e8;">
    <div class="event-header">
        <div>
            <div class="disease-title">
                <div class="disease-icon amazing-icon">❌</div>
                Payment Error
            </div>
        </div>
    </div>
    <div class="medical-details">
        <div class="detail-item">
            <div class="detail-label">Error Message</div>
            <div class="detail-value" style="color: #dc3545;"><?= htmlspecialchars($error_message) ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Error Code</div>
            <div class="detail-value"><?= htmlspecialchars($error_code) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$payments = mysqli_query($conn,"
    SELECT bill_id, amount, due_date, payment_method,
           CASE 
               WHEN payment_method IS NOT NULL AND payment_method != '' THEN 'Paid'
               WHEN due_date < CURDATE() THEN 'Overdue'
               WHEN due_date = CURDATE() THEN 'Due Today'
               ELSE 'Pending'
           END as status,
           description
    FROM medical_bills
    WHERE patient_id=$patient_id
    ORDER BY due_date DESC
");

if (mysqli_num_rows($payments) > 0):
    while ($payment = mysqli_fetch_assoc($payments)):
?>
<div class="medical-event amazing-card">
    <div class="event-header">
        <div>
            <div class="disease-title">
                <div class="disease-icon amazing-icon">💳</div>
                <?= htmlspecialchars($payment['description']) ?>
                <span class="status-badge <?= strtolower($payment['status']) ?>"><?= $payment['status'] ?></span>
            </div>
        </div>
    </div>
    
    <div class="medical-details">
        <div class="detail-item">
            <div class="detail-label">Bill Amount</div>
            <div class="detail-value">$<?= number_format($payment['amount'], 2) ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Due Date</div>
            <div class="detail-value"><?= $payment['due_date'] ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Status</div>
            <div class="detail-value"><?= $payment['status'] ?></div>
        </div>
    </div>
    
    <div class="medical-actions">
        <div class="action-group">
            <h4>💳 Payment Actions</h4>
            <?php if ($payment['status'] == 'Pending' || $payment['status'] == 'Overdue' || $payment['status'] == 'Due Today'): ?>
                <button class="btn amazing-button" onclick="showPaymentForm(<?= $payment['bill_id'] ?>, '<?= htmlspecialchars($payment['description']) ?>', this, <?= $patient_id ?>)">
                    💳 Pay Now
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endwhile; else: ?>
<div class="medical-event amazing-card">
    <div class="event-header">
        <div>
            <div class="disease-title">
                <div class="disease-icon amazing-icon">💳</div>
                No Payment Records
            </div>
        </div>
    </div>
    <div class="medical-details">
        <div class="detail-item">
            <div class="detail-label">Status</div>
            <div class="detail-value">No payment records found</div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>

<!-- QUICK ACTIONS -->
<div class="actions">
    <a class="btn amazing-button" href="add_history.php">
        ➕ Add Medical Condition
    </a>
    <a class="btn secondary amazing-button" href="my_appointments.php">
        📅 My Appointments
    </a>
    <a class="btn amazing-button" href="download_report.php">
        📄 Download Health Report
    </a>
    <a class="btn warning amazing-button" href="../dashboard/dashboard.php">
        🏠 Dashboard
    </a>
</div>

<!-- TOP BAR -->
<div class="topbar amazing-header" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; border-radius: 0; margin: 0; justify-content: center; text-align: center;">
    <div style="display: flex; align-items: center; justify-content: center; gap: 16px; width: 100%;">
        <div class="amazing-icon">🏥</div>
        <h2 class="golden-heading" data-text="MedoFolio" style="font-size: clamp(24px, 4vw, 36px); margin: 0; padding: 10px;">MedoFolio</h2>
    </div>
    <div style="position: absolute; right: 20px; display: flex; align-items: center; gap: 16px;">
        <button class="dark-toggle amazing-button" onclick="toggleDark()">🌙</button>
        <a href="../auth/logout.php" class="amazing-button" style="padding: 8px 16px; font-size: 14px;">Logout</a>
    </div>
</div>

<div style="height: 80px;"></div>

<!-- REPORTS MODAL -->
<div id="reportsModal" class="modal">
<div class="modal-content">
<button class="close-btn" onclick="closeReportsModal()">✖</button>
<h3>📂 My Reports</h3>

<?php
$reports = mysqli_query($conn,"
    SELECT report_id,file_name,file_path,disease_name,uploaded_at
    FROM medical_reports
    WHERE patient_id=$patient_id
    ORDER BY uploaded_at DESC
");

if (mysqli_num_rows($reports)>0):
while ($rep=mysqli_fetch_assoc($reports)):
?>
<div class="report-row">
    <div>
        <b><?= htmlspecialchars($rep['file_name']) ?></b><br>
        <small><?= htmlspecialchars($rep['disease_name']) ?> |
        <?= $rep['uploaded_at'] ?></small>
    </div>
    <div class="report-actions">
        <a class="btn small" target="_blank"
           href="view_pdf.php?file=<?= urlencode($rep['file_path']) ?>">View</a>
        
        <a class="btn secondary small" 
           href="view_pdf.php?file=<?= urlencode($rep['file_path']) ?>&download=1">Download</a>

        <form method="POST" action="delete_report.php"
              onsubmit="return confirm('Delete this report?')">
            <input type="hidden" name="report_id"
                   value="<?= $rep['report_id'] ?>">
            <button class="btn danger small">Delete</button>
        </form>
    </div>
</div>
<?php endwhile; else: ?>
<p>No reports uploaded.</p>
<?php endif; ?>
</div>
</div>

<script>
function toggleDark() {
    document.body.classList.toggle("dark");
    
    // Add a smooth transition effect
    document.body.style.transition = "all 0.3s ease";
    
    // Store preference in localStorage
    if (document.body.classList.contains("dark")) {
        localStorage.setItem("darkMode", "enabled");
    } else {
        localStorage.setItem("darkMode", "disabled");
    }
}

// Check for saved dark mode preference on page load
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem("darkMode") === "enabled") {
        document.body.classList.add("dark");
    }
});

// Add subtle animations on load
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.medical-event, .ai-summary-card, .amazing-card');
    elements.forEach((el, index) => {
        el.style.animation = `fadeInUp 0.6s ease ${index * 0.1}s both`;
    });
});

function showMap(hospitalType, btn) {
    console.log('showMap called with hospitalType:', hospitalType);
    
    // Find the map box using the button's parent
    const mapBox = btn.nextElementSibling;
    console.log('mapBox found:', mapBox);
    
    if (!mapBox) {
        console.error('Map box not found');
        alert('Map container not found. Please refresh the page.');
        return;
    }
    
    // Show immediate map with general search
    const searchQuery = encodeURIComponent(hospitalType + " hospital");
    const embedUrl = "https://www.google.com/maps?q=" + searchQuery + "&output=embed";
    const mapsSearchUrl = "https://www.google.com/maps/search/" + searchQuery;
    
    mapBox.style.display = "block";
    mapBox.innerHTML = `
        <div style="margin-bottom: 10px;">
            <a href="${mapsSearchUrl}" 
               target="_blank" 
               class="btn amazing-button" 
               style="display: inline-block; margin-bottom: 10px;">
                � Open in Google Maps
            </a>
        </div>
        <iframe src="${embedUrl}"
            width="100%" 
            height="300" 
            style="border:0; border-radius:12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);"
            allowfullscreen>
        </iframe>
        <div id="locationInfo" style="margin-top: 10px; font-size: 12px; color: #666; text-align: center;">
            📍 Getting your location for more precise results...
        </div>
    `;

    // Try to get location for better results (but don't wait)
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                const locationQuery = encodeURIComponent(hospitalType + " hospital near " + lat + "," + lon);
                const locationEmbedUrl = "https://www.google.com/maps?q=" + locationQuery + "&output=embed";
                const locationMapsUrl = "https://www.google.com/maps/search/" + locationQuery;
                
                // Update the iframe and link with location-based search
                mapBox.querySelector('iframe').src = locationEmbedUrl;
                mapBox.querySelector('.btn amazing-button').href = locationMapsUrl;
                document.getElementById('locationInfo').innerHTML = `📍 Location found: ${lat.toFixed(4)}, ${lon.toFixed(4)}`;
            },
            function(error) {
                console.log('Location access denied, using general search');
                document.getElementById('locationInfo').innerHTML = `📍 Using general search for ${hospitalType}`;
            },
            {
                timeout: 5000, // Shorter timeout
                maximumAge: 300000
            }
        );
    } else {
        document.getElementById('locationInfo').innerHTML = `📍 Geolocation not supported, using general search`;
    }
}

// Modal functions for reports
function openReportsModal() {
    document.getElementById('reportsModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeReportsModal() {
    document.getElementById('reportsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function showPaymentForm(billId, billDescription, btn, patientId) {
    console.log('showPaymentForm called with:', billId, billDescription, patientId);
    
    var paymentActions = btn.closest('.medical-actions');

    if (!paymentActions) {
        alert('Error: Payment container not found');
        return;
    }

    var existingForms = paymentActions.querySelectorAll('.payment-details');
    existingForms.forEach(f => f.remove());

    var formDiv = document.createElement('div');
    formDiv.className = 'payment-details';
    formDiv.style.padding = '20px';
    formDiv.style.background = '#f8f9fa';
    formDiv.style.borderRadius = '12px';
    formDiv.style.marginTop = '15px';
    formDiv.style.border = '2px solid #007bff';

    formDiv.innerHTML = `
        <h4 style="margin-bottom:15px;color:#007bff;">💳 Pay: ${billDescription}</h4>

        <form method="POST" action="process_payment.php">
            <input type="hidden" name="bill_id" value="${billId}">
            <input type="hidden" name="patient_id" value="${patientId}">

            <label>Payment Method</label>
            <select name="payment_method" id="paymentMethod${billId}" 
                onchange="togglePaymentFields(${billId})"
                style="width:100%;padding:10px;margin-bottom:10px;">
                
                <option value="">Select Method</option>
                <option value="credit_card">Card</option>
                <option value="upi">UPI</option>
                <option value="net_banking">Net Banking</option>
                <option value="wallet">Wallet</option>
            </select>

            <div id="creditCardFields${billId}" style="display:none;">
                <input type="text" name="card_number" placeholder="Card Number"><br>
                <input type="text" name="expiry" placeholder="MM/YY">
                <input type="text" name="cvv" placeholder="CVV">
            </div>

            <div id="upiFields${billId}" style="display:none;">
                <input type="text" name="upi_id" placeholder="UPI ID">
            </div>

            <div id="netBankingFields${billId}" style="display:none;">
                <input type="text" name="bank_name" placeholder="Bank Name"><br>
                <input type="text" name="account_number" placeholder="Account Number">
            </div>

            <div id="walletFields${billId}" style="display:none;">
                <input type="text" name="wallet_number" placeholder="Wallet Number">
            </div>

            <br>
            <button type="submit">Pay Now</button>
        </form>
    `;

    paymentActions.appendChild(formDiv);
}

function togglePaymentFields(billId) {
    var method = document.getElementById('paymentMethod' + billId).value;

    document.getElementById('creditCardFields' + billId).style.display = 'none';
    document.getElementById('upiFields' + billId).style.display = 'none';
    document.getElementById('netBankingFields' + billId).style.display = 'none';
    document.getElementById('walletFields' + billId).style.display = 'none';

    if (method === 'credit_card') {
        document.getElementById('creditCardFields' + billId).style.display = 'block';
    } else if (method === 'upi') {
        document.getElementById('upiFields' + billId).style.display = 'block';
    } else if (method === 'net_banking') {
        document.getElementById('netBankingFields' + billId).style.display = 'block';
    } else if (method === 'wallet') {
        document.getElementById('walletFields' + billId).style.display = 'block';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('reportsModal');
    if (event.target == modal) {
        closeReportsModal();
    }
}
</script>

</body>
</html>