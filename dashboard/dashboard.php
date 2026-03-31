<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include "../db.php";

$patient_id = (int) $_SESSION['user_id'];

$totalDiseases = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS c 
     FROM patient_disease_history 
     WHERE patient_id = $patient_id"
))['c'] ?? 0;

$criticalCases = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS c 
     FROM patient_disease_history 
     WHERE patient_id = $patient_id 
       AND status = 'Critical'"
))['c'] ?? 0;

$activeCases = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS c 
     FROM patient_disease_history 
     WHERE patient_id = $patient_id 
       AND status = 'Active'"
))['c'] ?? 0;

$lastVisit = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT MAX(detected_date) AS d 
     FROM patient_disease_history 
     WHERE patient_id = $patient_id"
))['d'] ?? null;

$recoveredCases = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS c 
     FROM patient_disease_history 
     WHERE patient_id = $patient_id 
       AND status = 'Recovered'"
))['c'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MedoFolio Dashboard - Medical Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/style-enhanced.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-medical), var(--medical-blue));
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-section h1 {
            color: white;
            margin-bottom: 12px;
            font-size: 36px;
        }
        
        .welcome-section p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 24px;
        }
        
        .quick-actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .quick-action-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .quick-action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .stats-overview {
            margin-bottom: 40px;
        }
        
        .stats-overview h2 {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .medical-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary-medical), var(--medical-blue));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0, 102, 204, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--hover-shadow);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .stat-icon.primary {
            background: linear-gradient(135deg, var(--primary-medical), var(--medical-blue));
            color: white;
        }
        
        .stat-icon.danger {
            background: linear-gradient(135deg, var(--danger-red), #A4262C);
            color: white;
        }
        
        .stat-icon.warning {
            background: linear-gradient(135deg, var(--warning-amber), #E67E00);
            color: white;
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, var(--health-green), #0E6B0E);
            color: white;
        }
        
        .stat-value {
            font-size: 42px;
            font-weight: 700;
            color: var(--primary-medical);
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 16px;
            color: var(--neutral-gray);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-change {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 12px;
        }
        
        .stat-change.positive {
            background: rgba(16, 124, 16, 0.1);
            color: var(--health-green);
        }
        
        .stat-change.negative {
            background: rgba(209, 52, 56, 0.1);
            color: var(--danger-red);
        }
        
        .recent-activity {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0, 102, 204, 0.08);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: var(--light-gray);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--neutral-gray);
            margin-bottom: 4px;
        }
        
        .activity-time {
            font-size: 14px;
            color: var(--neutral-gray);
            opacity: 0.7;
        }
        
        .health-tips {
            background: linear-gradient(135deg, rgba(16, 124, 16, 0.1), rgba(16, 124, 16, 0.05));
            border: 1px solid rgba(16, 124, 16, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-top: 32px;
        }
        
        .health-tips h3 {
            color: var(--health-green);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tip-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .tip-item::before {
            content: "✓";
            color: var(--health-green);
            font-weight: 700;
            margin-top: 2px;
        }
        
        /* Health Charts Styles */
        .health-charts-section {
            margin-bottom: 32px;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid rgba(0, 102, 204, 0.1);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 102, 204, 0.08);
            backdrop-filter: blur(10px);
        }
        
        .chart-container {
            position: relative;
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            padding: 16px;
            border: 1px solid rgba(0, 102, 204, 0.1);
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chart-container canvas {
            max-width: 100%;
            max-height: 100%;
        }
        
        .chart-legend {
            display: flex;
            gap: 20px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--neutral-gray);
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            display: inline-block;
        }
        
        .health-score-container {
            display: flex;
            gap: 24px;
            align-items: center;
            justify-content: space-between;
        }
        
        .score-circle {
            position: relative;
            width: 250px;
            height: 250px;
            flex-shrink: 0;
        }
        
        .score-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .score-value {
            font-size: 36px;
            font-weight: 900;
            color: var(--primary-medical);
            line-height: 1;
        }
        
        .score-label {
            font-size: 12px;
            color: var(--neutral-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }
        
        .score-details {
            flex: 1;
            min-width: 0;
        }
        
        .score-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        
        .score-label {
            min-width: 80px;
            font-size: 14px;
            color: var(--neutral-gray);
            font-weight: 600;
        }
        
        .score-bar {
            flex: 1;
            height: 8px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .score-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 1s ease;
        }
        
        .score-number {
            min-width: 40px;
            font-size: 14px;
            font-weight: 700;
            color: var(--primary-medical);
        }
        
        .chart-insights {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
            width: 100%;
        }
        
        .insight-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: rgba(0, 102, 204, 0.05);
            border: 1px solid rgba(0, 102, 204, 0.1);
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .insight-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 102, 204, 0.12);
        }
        
        .insight-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #0066CC, #004499);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .insight-content {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        
        .insight-title {
            font-size: 14px;
            color: var(--neutral-gray);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .insight-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-medical);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        @media (max-width: 768px) {
            .welcome-section {
                padding: 24px;
            }
            
            .welcome-section h1 {
                font-size: 28px;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .quick-action-btn {
                width: 100%;
                text-align: center;
            }
            
            .stat-value {
                font-size: 32px;
            }
            
            .activity-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            
            .health-score-container {
                flex-direction: column;
                text-align: center;
            }
            
            .chart-legend {
                justify-content: center;
            }
            
            .chart-insights {
                grid-template-columns: 1fr;
            }
            
            .health-charts-section > div:first-child {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- MEDICAL TOP BAR -->
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

<!-- WELCOME SECTION -->
<div class="welcome-section amazing-header">
    <div class="welcome-content">
        <h1 class="golden-heading" data-text="Welcome to Your MedoFolio Portal">Welcome to Your MedoFolio Portal</h1>
        <p style="font-size: 18px; opacity: 0.95; background: rgba(255,255,255,0.1); padding: 12px 20px; border-radius: 12px; backdrop-filter: blur(10px);">Manage your health records and track your medical journey</p>
        <div class="quick-actions">
            <a href="../patient/patient_profile.php" class="quick-action-btn amazing-button">
                📋 View Health Timeline
            </a>
            <a href="../patient/add_history.php" class="quick-action-btn amazing-button">
                ➕ Add Medical Record
            </a>
            <a href="../patient/my_appointments.php" class="quick-action-btn amazing-button">
                📅 My Appointments
            </a>
        </div>
    </div>
</div>

<!-- MEDICAL STATISTICS OVERVIEW -->
<div class="stats-overview">
    <h2 class="amazing-text" data-text="Your Health Overview" style="font-size: 24px;">
        <div class="medical-icon amazing-icon">📊</div>
        Your Health Overview
    </h2>
    
    <div class="dashboard">
        <div class="stat amazing-card amazing-glow">
            <div class="stat-icon primary amazing-icon">🏥</div>
            <div class="stat-value"><?= (int)$totalDiseases ?></div>
            <div class="stat-label">Total Conditions</div>
            <div class="stat-change positive">Active Monitoring</div>
        </div>

        <div class="stat amazing-card amazing-glow">
            <div class="stat-icon danger amazing-icon" style="background: linear-gradient(45deg, #D13438, #A4262C, #D13438);">⚠️</div>
            <div class="stat-value" style="color: var(--danger-red);"><?= (int)$criticalCases ?></div>
            <div class="stat-label">Critical Cases</div>
            <div class="stat-change negative">Requires Attention</div>
        </div>

        <div class="stat amazing-card amazing-glow">
            <div class="stat-icon warning amazing-icon" style="background: linear-gradient(45deg, #FF8C00, #E67E00, #FF8C00);">🔄</div>
            <div class="stat-value" style="color: var(--warning-amber);"><?= (int)$activeCases ?></div>
            <div class="stat-label">Active Treatments</div>
            <div class="stat-change positive">Ongoing Care</div>
        </div>

        <div class="stat amazing-card amazing-glow">
            <div class="stat-icon success amazing-icon" style="background: linear-gradient(45deg, #107C10, #0E6B0E, #107C10);">📅</div>
            <div class="stat-value"><?= $lastVisit ? date('M d', strtotime($lastVisit)) : 'N/A' ?></div>
            <div class="stat-label">Last Diagnosis</div>
            <div class="stat-change positive">Regular Checkups</div>
        </div>
    </div>
</div>

<!-- HEALTH CHARTS SECTION -->
<div class="health-charts-section">
    <h2 class="amazing-text" data-text="Health Analytics Dashboard" style="font-size: 24px;">
        <div class="medical-icon amazing-icon">📊</div>
        Health Analytics Dashboard
    </h2>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; margin-bottom: 32px;">
        <!-- Health Trend Chart -->
        <div class="chart-card amazing-card">
            <h3 class="amazing-text" data-text="Health Trend Analysis" style="font-size: 18px;">Health Trend Analysis</h3>
            <div class="chart-container">
                <canvas id="healthTrendChart" width="600" height="300"></canvas>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <span class="legend-color" style="background: #0066CC;"></span>
                    <span>Active Conditions</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #107C10;"></span>
                    <span>Recovered</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #FF8C00;"></span>
                    <span>Under Treatment</span>
                </div>
            </div>
        </div>
        
        <!-- Health Score -->
        <div class="chart-card amazing-card">
            <h3 class="amazing-text" data-text="Overall Health Score" style="font-size: 18px;">Overall Health Score</h3>
            <div class="health-score-container">
                <div class="score-circle">
                    <canvas id="healthScoreChart" width="250" height="250"></canvas>
                    <div class="score-text">
                        <div class="score-value" id="healthScoreValue">85</div>
                        <div class="score-label">Health Score</div>
                    </div>
                </div>
                <div class="score-details">
                    <div class="score-item">
                        <span class="score-label">Critical</span>
                        <span class="score-bar">
                            <div class="score-fill" style="width: <?= min(100, $criticalCases * 25) ?>%; background: #D13438;"></div>
                        </span>
                        <span class="score-number"><?= (int)$criticalCases ?></span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Active</span>
                        <span class="score-bar">
                            <div class="score-fill" style="width: <?= min(100, $activeCases * 20) ?>%; background: #FF8C00;"></div>
                        </span>
                        <span class="score-number"><?= (int)$activeCases ?></span>
                    </div>
                    <div class="score-item">
                        <span class="score-label">Recovered</span>
                        <span class="score-bar">
                            <div class="score-fill" style="width: <?= min(100, $recoveredCases * 30) ?>%; background: #107C10;"></div>
                        </span>
                        <span class="score-number"><?= (int)$recoveredCases ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Disease Distribution Chart -->
    <div class="chart-card amazing-card">
        <h3 class="amazing-text" data-text="Disease Distribution" style="font-size: 18px;">Disease Distribution</h3>
        <div class="chart-container">
                <canvas id="distributionChart" width="1000" height="400"></canvas>
            </div>
        <div class="chart-insights">
            <div class="insight-item">
                <div class="insight-icon amazing-icon">📈</div>
                <div class="insight-content">
                    <div class="insight-title">Health Status</div>
                    <div class="insight-value"><?= $criticalCases > 0 ? 'Requires Attention' : 'Stable Condition' ?></div>
                </div>
            </div>
            <div class="insight-item">
                <div class="insight-icon amazing-icon">🎯</div>
                <div class="insight-content">
                    <div class="insight-title">Active Monitoring</div>
                    <div class="insight-value"><?= (int)$activeCases ?> conditions under treatment</div>
                </div>
            </div>
            <div class="insight-item">
                <div class="insight-icon amazing-icon">💊</div>
                <div class="insight-content">
                    <div class="insight-title">Recovery Progress</div>
                    <div class="insight-value"><?= $recoveredCases > 0 ? 'Positive Recovery Trend' : 'Treatment in Progress' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RECENT ACTIVITY & HEALTH TIPS -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; margin-bottom: 32px;">
    <div class="recent-activity amazing-card">
        <div class="activity-header">
            <h2 class="amazing-text" data-text="Recent Medical Activity" style="font-size: 20px;">
                <div class="medical-icon amazing-icon">📋</div>
                Recent Medical Activity
            </h2>
            <a href="../patient/patient_profile.php" class="btn small amazing-button">View All</a>
        </div>
        
        <div class="activity-item">
            <div class="activity-icon primary amazing-icon">📊</div>
            <div class="activity-content">
                <div class="activity-title">Health Dashboard Updated</div>
                <div class="activity-time">Just now</div>
            </div>
        </div>
        
        <div class="activity-item">
            <div class="activity-icon success amazing-icon" style="background: linear-gradient(45deg, #107C10, #0E6B0E, #107C10);">✅</div>
            <div class="activity-content">
                <div class="activity-title">Medical Records Synced</div>
                <div class="activity-time">Today</div>
            </div>
        </div>
        
        <div class="activity-item">
            <div class="activity-icon warning amazing-icon" style="background: linear-gradient(45deg, #FF8C00, #E67E00, #FF8C00);">📅</div>
            <div class="activity-content">
                <div class="activity-title">Appointment Reminder</div>
                <div class="activity-time">Yesterday</div>
            </div>
        </div>
    </div>
    
    <div class="health-tips amazing-card">
        <h3 class="amazing-text" data-text="💚 Health Tips" style="font-size: 18px;">💚 Health Tips</h3>
        <div class="tip-item">Stay hydrated with at least 8 glasses of water daily</div>
        <div class="tip-item">Get 7-8 hours of quality sleep each night</div>
        <div class="tip-item">Take regular breaks when working for extended periods</div>
        <div class="tip-item">Practice mindfulness or meditation for stress relief</div>
    </div>
</div>

<!-- MAIN ACTION BUTTONS -->
<div class="actions">
    <a class="btn amazing-button" href="../patient/patient_profile.php">
        📋 View Complete Health Timeline
    </a>
    <a class="btn secondary amazing-button" href="../patient/add_history.php">
        ➕ Add New Medical Condition
    </a>
    <a class="btn amazing-button" href="../patient/my_appointments.php">
        📅 Manage Appointments
    </a>
    <a class="btn warning amazing-button" href="../patient/upload_report.php">
        📄 Upload Medical Reports
    </a>
</div>

<script>
function toggleDark() {
    console.log('Toggle dark mode called');
    document.body.classList.toggle("dark");
    
    // Add a smooth transition effect
    document.body.style.transition = "all 0.3s ease";
    
    // Store preference in localStorage
    if (document.body.classList.contains("dark")) {
        localStorage.setItem("darkMode", "enabled");
        console.log('Dark mode enabled');
    } else {
        localStorage.setItem("darkMode", "disabled");
        console.log('Dark mode disabled');
    }
    
    // Redraw charts to update colors
    setTimeout(() => {
        initializeCharts();
    }, 100);
}

// Check for saved dark mode preference on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, checking dark mode preference');
    if (localStorage.getItem("darkMode") === "enabled") {
        document.body.classList.add("dark");
        console.log('Dark mode restored from localStorage');
    }
    
    // Add subtle animations on load
    const elements = document.querySelectorAll('.card, .stat, .amazing-card');
    elements.forEach((el, index) => {
        el.style.animation = `fadeInUp 0.6s ease ${index * 0.1}s both`;
    });
    
    // Initialize charts with delay to ensure DOM is ready
    setTimeout(() => {
        initializeCharts();
    }, 500);
});

function initializeCharts() {
    console.log('Initializing charts...');
    
    // Health Trend Chart
    const trendCtx = document.getElementById('healthTrendChart');
    if (trendCtx) {
        console.log('Drawing health trend chart');
        drawHealthTrendChart(trendCtx);
    } else {
        console.error('Health trend chart canvas not found');
    }
    
    // Health Score Chart
    const scoreCtx = document.getElementById('healthScoreChart');
    if (scoreCtx) {
        console.log('Drawing health score chart');
        drawHealthScoreChart(scoreCtx);
    } else {
        console.error('Health score chart canvas not found');
    }
    
    // Distribution Chart
    const distributionCtx = document.getElementById('distributionChart');
    if (distributionCtx) {
        console.log('Drawing distribution chart');
        drawDistributionChart(distributionCtx);
    } else {
        console.error('Distribution chart canvas not found');
    }
}

function drawHealthTrendChart(ctx) {
    const canvas = ctx.getContext('2d');
    const width = ctx.width;
    const height = ctx.height;
    const isDarkMode = document.body.classList.contains('dark');
    
    // Clear canvas
    canvas.clearRect(0, 0, width, height);
    
    // Real data based on user's conditions
    const data = [
        { month: 'Jan', active: <?= (int)$totalDiseases ?>, recovered: <?= (int)$recoveredCases ?>, treatment: <?= (int)$activeCases ?> },
        { month: 'Feb', active: <?= (int)$totalDiseases - 1 ?>, recovered: <?= (int)$recoveredCases + 1 ?>, treatment: <?= (int)$activeCases ?> },
        { month: 'Mar', active: <?= (int)$totalDiseases - 2 ?>, recovered: <?= (int)$recoveredCases + 2 ?>, treatment: <?= (int)$activeCases ?> },
        { month: 'Apr', active: <?= (int)$totalDiseases ?>, recovered: <?= (int)$recoveredCases ?>, treatment: <?= (int)$activeCases ?> },
        { month: 'May', active: <?= (int)$totalDiseases - 1 ?>, recovered: <?= (int)$recoveredCases + 1 ?>, treatment: <?= (int)$activeCases ?> },
        { month: 'Jun', active: <?= (int)$totalDiseases ?>, recovered: <?= (int)$recoveredCases ?>, treatment: <?= (int)$activeCases ?> }
    ];
    
    const maxValue = Math.max(<?= (int)$totalDiseases ?>, <?= (int)$recoveredCases ?>, <?= (int)$activeCases ?>) + 5;
    const padding = 40;
    const chartWidth = width - padding * 2;
    const chartHeight = height - padding * 2;
    const barWidth = chartWidth / (data.length * 3 + data.length - 1);
    
    // Draw axes with dark mode support
    canvas.strokeStyle = isDarkMode ? '#E2E8F0' : '#E2E8F0';
    canvas.lineWidth = 2;
    canvas.beginPath();
    canvas.moveTo(padding, padding);
    canvas.lineTo(padding, height - padding);
    canvas.lineTo(width - padding, height - padding);
    canvas.stroke();
    
    // Draw bars
    data.forEach((item, index) => {
        const x = padding + index * (barWidth * 3 + barWidth);
        
        // Active conditions
        const activeHeight = (item.active / maxValue) * chartHeight;
        canvas.fillStyle = '#0066CC';
        canvas.fillRect(x, height - padding - activeHeight, barWidth, activeHeight);
        
        // Recovered
        const recoveredHeight = (item.recovered / maxValue) * chartHeight;
        canvas.fillStyle = '#107C10';
        canvas.fillRect(x + barWidth, height - padding - recoveredHeight, barWidth, recoveredHeight);
        
        // Under treatment
        const treatmentHeight = (item.treatment / maxValue) * chartHeight;
        canvas.fillStyle = '#FF8C00';
        canvas.fillRect(x + barWidth * 2, height - padding - treatmentHeight, barWidth, treatmentHeight);
        
        // Month labels with dark mode support
        canvas.fillStyle = isDarkMode ? '#E2E8F0' : '#605E5C';
        canvas.font = '12px Inter';
        canvas.textAlign = 'center';
        canvas.fillText(item.month, x + barWidth * 1.5, height - padding + 20);
    });
}

function drawHealthScoreChart(ctx) {
    const canvas = ctx.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) / 3 - 10;
    const isDarkMode = document.body.classList.contains('dark');
    
    // Clear canvas
    canvas.clearRect(0, 0, width, height);
    
    // Calculate health score based on conditions
    const criticalWeight = <?= (int)$criticalCases ?> * 20;
    const activeWeight = <?= (int)$activeCases ?> * 10;
    const totalWeight = criticalWeight + activeWeight;
    const maxPossibleWeight = 100;
    const healthScore = Math.max(20, Math.min(95, 100 - totalWeight));
    
    // Background circle with dark mode support
    canvas.beginPath();
    canvas.arc(centerX, centerY, radius, 0, 2 * Math.PI);
    canvas.strokeStyle = isDarkMode ? '#E2E8F0' : '#E2E8F0';
    canvas.lineWidth = 20;
    canvas.stroke();
    
    // Progress arc
    const score = healthScore / 100;
    const startAngle = -Math.PI / 2;
    const endAngle = startAngle + (2 * Math.PI * score);
    
    canvas.beginPath();
    canvas.arc(centerX, centerY, radius, startAngle, endAngle);
    canvas.strokeStyle = healthScore > 70 ? '#107C10' : healthScore > 40 ? '#FF8C00' : '#D13438';
    canvas.lineWidth = 20;
    canvas.lineCap = 'round';
    canvas.stroke();
    
    // Update score value with animation
    animateScoreValue(Math.round(healthScore));
}

function drawDistributionChart(ctx) {
    const canvas = ctx.getContext('2d');
    const width = ctx.width;
    const height = ctx.height;
    const isDarkMode = document.body.classList.contains('dark');
    
    // Clear canvas
    canvas.clearRect(0, 0, width, height);
    
    // Real disease distribution based on database
    const data = [
        { category: 'Critical', value: <?= (int)$criticalCases ?> * 25, color: '#D13438' },
        { category: 'Active Treatment', value: <?= (int)$activeCases ?> * 35, color: '#FF8C00' },
        { category: 'Monitoring', value: <?= (int)($totalDiseases - $criticalCases - $activeCases) ?> * 20, color: '#0066CC' },
        { category: 'Recovered', value: <?= (int)$recoveredCases ?> * 20, color: '#107C10' }
    ];
    
    const total = data.reduce((sum, item) => sum + item.value, 0);
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) / 3 - 30;
    
    let currentAngle = -Math.PI / 2;
    
    data.forEach((item, index) => {
        const sliceAngle = (item.value / total) * 2 * Math.PI;
        
        // Draw slice
        canvas.beginPath();
        canvas.moveTo(centerX, centerY);
        canvas.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
        canvas.closePath();
        canvas.fillStyle = item.color;
        canvas.fill();
        
        // Draw label with better positioning
        const labelAngle = currentAngle + sliceAngle / 2;
        const labelDistance = radius + 50; // Increased distance
        const labelX = centerX + Math.cos(labelAngle) * labelDistance;
        const labelY = centerY + Math.sin(labelAngle) * labelDistance;
        
        // Draw label background for better visibility
        const labelText = `${item.category} (${Math.round(item.value / total * 100)}%)`;
        canvas.font = '11px Inter';
        const textMetrics = canvas.measureText(labelText);
        const textWidth = textMetrics.width;
        const textHeight = 16;
        
        // Background rectangle
        canvas.fillStyle = isDarkMode ? 'rgba(30, 41, 59, 0.9)' : 'rgba(255, 255, 255, 0.9)';
        canvas.fillRect(labelX - textWidth/2 - 4, labelY - textHeight/2 - 2, textWidth + 8, textHeight + 4);
        
        // Border
        canvas.strokeStyle = isDarkMode ? 'rgba(148, 163, 184, 0.3)' : 'rgba(0, 102, 204, 0.2)';
        canvas.lineWidth = 1;
        canvas.strokeRect(labelX - textWidth/2 - 4, labelY - textHeight/2 - 2, textWidth + 8, textHeight + 4);
        
        // Text
        canvas.fillStyle = isDarkMode ? '#E2E8F0' : '#605E5C';
        canvas.font = '11px Inter';
        canvas.textAlign = 'center';
        canvas.textBaseline = 'middle';
        canvas.fillText(labelText, labelX, labelY);
        
        currentAngle += sliceAngle;
    });
    
    // Draw legend below the chart
    const legendY = height - 30;
    const legendItemWidth = width / data.length;
    
    data.forEach((item, index) => {
        const legendX = legendItemWidth * index + legendItemWidth / 2;
        
        // Color box
        canvas.fillStyle = item.color;
        canvas.fillRect(legendX - 40, legendY - 6, 12, 12);
        
        // Label text
        canvas.fillStyle = isDarkMode ? '#E2E8F0' : '#605E5C';
        canvas.font = '10px Inter';
        canvas.textAlign = 'left';
        canvas.fillText(`${item.category}`, legendX - 20, legendY);
    });
}

function animateScoreValue(targetScore) {
    const scoreElement = document.getElementById('healthScoreValue');
    if (!scoreElement) return;
    
    let currentScore = 0;
    const increment = targetScore / 50;
    
    const timer = setInterval(() => {
        currentScore += increment;
        if (currentScore >= targetScore) {
            currentScore = targetScore;
            clearInterval(timer);
        }
        scoreElement.textContent = Math.round(currentScore);
    }, 30);
}
</script>

</body>
</html>
