<?php
/**
 * Smart Features Engine
 * Implements advanced features like trend detection, emergency alerts, and predictive analytics
 */

class SmartFeatures {
    private $emergencyThresholds = [
        'blood_pressure' => [
            'systolic_critical' => 180,
            'diastolic_critical' => 120,
            'systolic_high' => 140,
            'diastolic_high' => 90
        ],
        'heart_rate' => [
            'critical_high' => 150,
            'critical_low' => 40,
            'high' => 120,
            'low' => 50
        ],
        'temperature' => [
            'critical_high' => 104,
            'critical_low' => 95,
            'high' => 102,
            'low' => 96
        ],
        'oxygen_saturation' => [
            'critical' => 88,
            'low' => 92,
            'normal' => 95
        ]
    ];

    private $trendAnalysisPeriods = [
        'short_term' => 3, // Last 3 readings
        'medium_term' => 7, // Last 7 readings
        'long_term' => 30 // Last 30 readings
    ];

    public function detectEmergencyAlerts($currentVitals, $patientId = null) {
        $alerts = [];
        $severity = 'low';

        // Blood pressure emergency detection
        if (isset($currentVitals['blood_pressure'])) {
            $bpParts = explode('/', $currentVitals['blood_pressure']);
            if (count($bpParts) === 2) {
                $systolic = (int)$bpParts[0];
                $diastolic = (int)$bpParts[1];

                if ($systolic >= $this->emergencyThresholds['blood_pressure']['systolic_critical'] || 
                    $diastolic >= $this->emergencyThresholds['blood_pressure']['diastolic_critical']) {
                    $alerts[] = [
                        'type' => 'blood_pressure',
                        'message' => "Critical blood pressure detected: {$currentVitals['blood_pressure']}",
                        'severity' => 'critical',
                        'action' => 'immediate_medical_attention'
                    ];
                    $severity = 'critical';
                } elseif ($systolic >= $this->emergencyThresholds['blood_pressure']['systolic_high'] || 
                         $diastolic >= $this->emergencyThresholds['blood_pressure']['diastolic_high']) {
                    $alerts[] = [
                        'type' => 'blood_pressure',
                        'message' => "High blood pressure detected: {$currentVitals['blood_pressure']}",
                        'severity' => 'high',
                        'action' => 'medical_consultation'
                    ];
                    $severity = $this->getHigherSeverity($severity, 'high');
                }
            }
        }

        // Heart rate emergency detection
        if (isset($currentVitals['heart_rate'])) {
            $heartRate = (int)$currentVitals['heart_rate'];

            if ($heartRate >= $this->emergencyThresholds['heart_rate']['critical_high'] || 
                $heartRate <= $this->emergencyThresholds['heart_rate']['critical_low']) {
                $alerts[] = [
                    'type' => 'heart_rate',
                    'message' => "Critical heart rate detected: {$heartRate} bpm",
                    'severity' => 'critical',
                    'action' => 'immediate_medical_attention'
                ];
                $severity = 'critical';
            } elseif ($heartRate >= $this->emergencyThresholds['heart_rate']['high'] || 
                     $heartRate <= $this->emergencyThresholds['heart_rate']['low']) {
                $alerts[] = [
                    'type' => 'heart_rate',
                    'message' => "Abnormal heart rate detected: {$heartRate} bpm",
                    'severity' => 'high',
                    'action' => 'medical_consultation'
                ];
                $severity = $this->getHigherSeverity($severity, 'high');
            }
        }

        // Temperature emergency detection
        if (isset($currentVitals['temperature'])) {
            $temperature = (float)$currentVitals['temperature'];

            if ($temperature >= $this->emergencyThresholds['temperature']['critical_high'] || 
                $temperature <= $this->emergencyThresholds['temperature']['critical_low']) {
                $alerts[] = [
                    'type' => 'temperature',
                    'message' => "Critical temperature detected: {$temperature}°F",
                    'severity' => 'critical',
                    'action' => 'immediate_medical_attention'
                ];
                $severity = 'critical';
            } elseif ($temperature >= $this->emergencyThresholds['temperature']['high'] || 
                     $temperature <= $this->emergencyThresholds['temperature']['low']) {
                $alerts[] = [
                    'type' => 'temperature',
                    'message' => "Abnormal temperature detected: {$temperature}°F",
                    'severity' => 'medium',
                    'action' => 'monitor_closely'
                ];
                $severity = $this->getHigherSeverity($severity, 'medium');
            }
        }

        // Oxygen saturation emergency detection
        if (isset($currentVitals['oxygen_saturation'])) {
            $oxygen = (int)$currentVitals['oxygen_saturation'];

            if ($oxygen <= $this->emergencyThresholds['oxygen_saturation']['critical']) {
                $alerts[] = [
                    'type' => 'oxygen_saturation',
                    'message' => "Critical oxygen saturation detected: {$oxygen}%",
                    'severity' => 'critical',
                    'action' => 'immediate_medical_attention'
                ];
                $severity = 'critical';
            } elseif ($oxygen <= $this->emergencyThresholds['oxygen_saturation']['low']) {
                $alerts[] = [
                    'type' => 'oxygen_saturation',
                    'message' => "Low oxygen saturation detected: {$oxygen}%",
                    'severity' => 'high',
                    'action' => 'medical_consultation'
                ];
                $severity = $this->getHigherSeverity($severity, 'high');
            }
        }

        return [
            'alerts' => $alerts,
            'overall_severity' => $severity,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function analyzeVitalsTrend($vitalsHistory) {
        if (count($vitalsHistory) < 2) {
            return [
                'trend' => 'insufficient_data',
                'analysis' => 'Not enough data points for trend analysis',
                'recommendations' => []
            ];
        }

        $trends = [];
        $overallTrend = 'stable';
        $recommendations = [];

        // Analyze blood pressure trend
        if (isset($vitalsHistory[0]['blood_pressure']) && isset($vitalsHistory[1]['blood_pressure'])) {
            $bpTrend = $this->analyzeBloodPressureTrend($vitalsHistory);
            $trends[] = $bpTrend;
            if ($bpTrend['direction'] === 'worsening') {
                $overallTrend = 'worsening';
                $recommendations[] = "Blood pressure trending upward - consider medication adjustment";
            }
        }

        // Analyze heart rate trend
        if (isset($vitalsHistory[0]['heart_rate']) && isset($vitalsHistory[1]['heart_rate'])) {
            $hrTrend = $this->analyzeHeartRateTrend($vitalsHistory);
            $trends[] = $hrTrend;
            if ($hrTrend['direction'] === 'worsening') {
                $overallTrend = 'worsening';
                $recommendations[] = "Heart rate showing concerning trend - cardiac evaluation recommended";
            }
        }

        // Analyze temperature trend
        if (isset($vitalsHistory[0]['temperature']) && isset($vitalsHistory[1]['temperature'])) {
            $tempTrend = $this->analyzeTemperatureTrend($vitalsHistory);
            $trends[] = $tempTrend;
            if ($tempTrend['direction'] === 'worsening') {
                $overallTrend = 'worsening';
                $recommendations[] = "Temperature trending upward - infection monitoring required";
            }
        }

        // Analyze oxygen saturation trend
        if (isset($vitalsHistory[0]['oxygen_saturation']) && isset($vitalsHistory[1]['oxygen_saturation'])) {
            $oxyTrend = $this->analyzeOxygenTrend($vitalsHistory);
            $trends[] = $oxyTrend;
            if ($oxyTrend['direction'] === 'worsening') {
                $overallTrend = 'worsening';
                $recommendations[] = "Oxygen saturation declining - respiratory assessment needed";
            }
        }

        return [
            'trend' => $overallTrend,
            'individual_trends' => $trends,
            'recommendations' => $recommendations,
            'analysis_period' => count($vitalsHistory) . ' readings',
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }

    private function analyzeBloodPressureTrend($vitalsHistory) {
        $latest = explode('/', $vitalsHistory[0]['blood_pressure']);
        $previous = explode('/', $vitalsHistory[1]['blood_pressure']);
        
        if (count($latest) === 2 && count($previous) === 2) {
            $systolicChange = (int)$latest[0] - (int)$previous[0];
            $diastolicChange = (int)$latest[1] - (int)$previous[1];
            
            $direction = 'stable';
            $significance = 'minor';
            
            if (abs($systolicChange) >= 10 || abs($diastolicChange) >= 10) {
                $significance = 'significant';
                if ($systolicChange > 5 || $diastolicChange > 5) {
                    $direction = 'worsening';
                } elseif ($systolicChange < -5 || $diastolicChange < -5) {
                    $direction = 'improving';
                }
            }
            
            return [
                'vital' => 'blood_pressure',
                'direction' => $direction,
                'significance' => $significance,
                'change' => "Systolic: {$systolicChange}mmHg, Diastolic: {$diastolicChange}mmHg"
            ];
        }
        
        return ['vital' => 'blood_pressure', 'direction' => 'unknown', 'error' => 'Invalid data format'];
    }

    private function analyzeHeartRateTrend($vitalsHistory) {
        $latest = (int)$vitalsHistory[0]['heart_rate'];
        $previous = (int)$vitalsHistory[1]['heart_rate'];
        $change = $latest - $previous;
        
        $direction = 'stable';
        $significance = 'minor';
        
        if (abs($change) >= 10) {
            $significance = 'significant';
            if ($change > 5) {
                $direction = 'worsening';
            } elseif ($change < -5) {
                $direction = 'improving';
            }
        }
        
        return [
            'vital' => 'heart_rate',
            'direction' => $direction,
            'significance' => $significance,
            'change' => "{$change} bpm"
        ];
    }

    private function analyzeTemperatureTrend($vitalsHistory) {
        $latest = (float)$vitalsHistory[0]['temperature'];
        $previous = (float)$vitalsHistory[1]['temperature'];
        $change = $latest - $previous;
        
        $direction = 'stable';
        $significance = 'minor';
        
        if (abs($change) >= 1.0) {
            $significance = 'significant';
            if ($change > 0.5) {
                $direction = 'worsening';
            } elseif ($change < -0.5) {
                $direction = 'improving';
            }
        }
        
        return [
            'vital' => 'temperature',
            'direction' => $direction,
            'significance' => $significance,
            'change' => "{$change}°F"
        ];
    }

    private function analyzeOxygenTrend($vitalsHistory) {
        $latest = (int)$vitalsHistory[0]['oxygen_saturation'];
        $previous = (int)$vitalsHistory[1]['oxygen_saturation'];
        $change = $latest - $previous;
        
        $direction = 'stable';
        $significance = 'minor';
        
        if (abs($change) >= 2) {
            $significance = 'significant';
            if ($change < -1) {
                $direction = 'worsening';
            } elseif ($change > 1) {
                $direction = 'improving';
            }
        }
        
        return [
            'vital' => 'oxygen_saturation',
            'direction' => $direction,
            'significance' => $significance,
            'change' => "{$change}%"
        ];
    }

    public function generatePredictiveInsights($patientData, $vitalsHistory, $medicalHistory) {
        $insights = [];
        $riskFactors = [];
        $predictions = [];

        // Age-based predictions
        if (isset($patientData['age'])) {
            $age = (int)$patientData['age'];
            if ($age > 65) {
                $riskFactors[] = 'Elderly patient - higher risk for cardiovascular events';
                $predictions[] = 'Regular cardiac monitoring recommended';
            } elseif ($age < 12) {
                $riskFactors[] = 'Pediatric patient - requires specialized care';
                $predictions[] = 'Pediatric specialist consultation recommended for complex cases';
            }
        }

        // Chronic condition predictions
        if (!empty($medicalHistory)) {
            $chronicConditions = [];
            foreach ($medicalHistory as $condition) {
                $conditionName = strtolower($condition['disease_name'] ?? $condition);
                if (in_array($conditionName, ['diabetes', 'hypertension', 'heart disease', 'asthma', 'copd'])) {
                    $chronicConditions[] = $conditionName;
                }
            }

            if (in_array('diabetes', $chronicConditions)) {
                $riskFactors[] = 'Diabetes - increased risk of cardiovascular and kidney complications';
                $predictions[] = 'Regular HbA1c monitoring and kidney function tests recommended';
            }

            if (in_array('hypertension', $chronicConditions)) {
                $riskFactors[] = 'Hypertension - increased risk of stroke and heart disease';
                $predictions[] = 'Blood pressure monitoring at home recommended';
            }
        }

        // Vitals-based predictions
        if (!empty($vitalsHistory) && count($vitalsHistory) >= 3) {
            $trendAnalysis = $this->analyzeVitalsTrend(array_slice($vitalsHistory, 0, 3));
            if ($trendAnalysis['trend'] === 'worsening') {
                $riskFactors[] = 'Declining vital signs trend detected';
                $predictions[] = 'Increased monitoring frequency recommended';
            }
        }

        // Generate health score prediction
        $healthScore = $this->calculateHealthScore($patientData, $vitalsHistory, $medicalHistory);
        
        return [
            'insights' => $insights,
            'risk_factors' => $riskFactors,
            'predictions' => $predictions,
            'health_score' => $healthScore,
            'recommendations' => $this->generatePredictiveRecommendations($healthScore, $riskFactors),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function calculateHealthScore($patientData, $vitalsHistory, $medicalHistory) {
        $score = 100; // Start with perfect score
        
        // Age factor
        if (isset($patientData['age'])) {
            $age = (int)$patientData['age'];
            if ($age > 65) $score -= 15;
            elseif ($age > 50) $score -= 10;
            elseif ($age > 35) $score -= 5;
        }

        // Chronic conditions factor
        if (!empty($medicalHistory)) {
            foreach ($medicalHistory as $condition) {
                $conditionName = strtolower($condition['disease_name'] ?? $condition);
                if (in_array($conditionName, ['diabetes', 'heart disease', 'cancer', 'stroke'])) {
                    $score -= 20;
                } elseif (in_array($conditionName, ['hypertension', 'asthma', 'copd'])) {
                    $score -= 15;
                } else {
                    $score -= 5;
                }
            }
        }

        // Vitals factor
        if (!empty($vitalsHistory)) {
            $latestVitals = $vitalsHistory[0];
            
            // Blood pressure impact
            if (isset($latestVitals['blood_pressure'])) {
                $parts = explode('/', $latestVitals['blood_pressure']);
                if (count($parts) === 2) {
                    $systolic = (int)$parts[0];
                    $diastolic = (int)$parts[1];
                    if ($systolic > 140 || $diastolic > 90) $score -= 15;
                    elseif ($systolic > 130 || $diastolic > 80) $score -= 10;
                }
            }

            // Heart rate impact
            if (isset($latestVitals['heart_rate'])) {
                $hr = (int)$latestVitals['heart_rate'];
                if ($hr > 120 || $hr < 50) $score -= 15;
                elseif ($hr > 100 || $hr < 60) $score -= 10;
            }

            // Oxygen saturation impact
            if (isset($latestVitals['oxygen_saturation'])) {
                $oxy = (int)$latestVitals['oxygen_saturation'];
                if ($oxy < 90) $score -= 20;
                elseif ($oxy < 94) $score -= 10;
            }
        }

        return max(0, min(100, $score));
    }

    private function generatePredictiveRecommendations($healthScore, $riskFactors) {
        $recommendations = [];

        if ($healthScore < 50) {
            $recommendations[] = "High health risk detected - immediate medical evaluation recommended";
            $recommendations[] = "Consider hospital admission for close monitoring";
        } elseif ($healthScore < 70) {
            $recommendations[] = "Moderate health risk - schedule medical appointment within 1 week";
            $recommendations[] = "Increase monitoring frequency of vital signs";
        } elseif ($healthScore < 85) {
            $recommendations[] = "Mild health risk - routine medical follow-up recommended";
            $recommendations[] = "Focus on preventive care measures";
        } else {
            $recommendations[] = "Good health status - continue routine monitoring";
            $recommendations[] = "Maintain healthy lifestyle practices";
        }

        // Add specific recommendations based on risk factors
        foreach ($riskFactors as $factor) {
            if (strpos(strtolower($factor), 'diabetes') !== false) {
                $recommendations[] = "Regular blood sugar monitoring and dietary management";
            }
            if (strpos(strtolower($factor), 'cardiovascular') !== false) {
                $recommendations[] = "Cardiovascular exercise and heart-healthy diet";
            }
            if (strpos(strtolower($factor), 'elderly') !== false) {
                $recommendations[] = "Fall prevention and regular comprehensive health assessments";
            }
        }

        return array_unique($recommendations);
    }

    private function getHigherSeverity($current, $new) {
        $severityLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        return $severityLevels[$new] > $severityLevels[$current] ? $new : $current;
    }

    public function createAutomaticAlerts($patientId, $doctorId, $vitals) {
        global $conn;
        
        $emergencyAnalysis = $this->detectEmergencyAlerts($vitals, $patientId);
        
        foreach ($emergencyAnalysis['alerts'] as $alert) {
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO alerts (patient_id, doctor_id, message, severity, alert_type) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            
            $message = $alert['message'];
            $severity = $alert['severity'];
            $alertType = 'emergency';
            
            mysqli_stmt_bind_param($stmt, "iisss", $patientId, $doctorId, $message, $severity, $alertType);
            mysqli_stmt_execute($stmt);
        }
        
        return $emergencyAnalysis;
    }
}

// API endpoint for smart features
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    include "../db.php";
    
    // Check if doctor is logged in
    if (!isset($_SESSION['doctor_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized access']);
        exit;
    }
    
    $smartFeatures = new SmartFeatures();
    $action = $_POST['action'];
    
    switch ($action) {
        case 'detect_emergencies':
            $vitals = json_decode($_POST['vitals'] ?? '{}', true);
            $patientId = (int)($_POST['patient_id'] ?? 0);
            $result = $smartFeatures->detectEmergencyAlerts($vitals, $patientId);
            echo json_encode($result);
            break;
            
        case 'analyze_trends':
            $vitalsHistory = json_decode($_POST['vitals_history'] ?? '[]', true);
            $result = $smartFeatures->analyzeVitalsTrend($vitalsHistory);
            echo json_encode($result);
            break;
            
        case 'predictive_insights':
            $patientData = json_decode($_POST['patient_data'] ?? '{}', true);
            $vitalsHistory = json_decode($_POST['vitals_history'] ?? '[]', true);
            $medicalHistory = json_decode($_POST['medical_history'] ?? '[]', true);
            $result = $smartFeatures->generatePredictiveInsights($patientData, $vitalsHistory, $medicalHistory);
            echo json_encode($result);
            break;
            
        case 'auto_alerts':
            $patientId = (int)($_POST['patient_id'] ?? 0);
            $doctorId = $_SESSION['doctor_id'];
            $vitals = json_decode($_POST['vitals'] ?? '{}', true);
            $result = $smartFeatures->createAutomaticAlerts($patientId, $doctorId, $vitals);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}
?>
