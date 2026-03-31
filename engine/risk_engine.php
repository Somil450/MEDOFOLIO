<?php
/**
 * Health Risk Assessment Engine
 * Rule-based AI system for calculating patient health risk scores
 */

class RiskEngine {
    private $riskFactors = [
        'age' => [
            'low_risk' => ['min' => 0, 'max' => 30, 'score' => 5],
            'medium_risk' => ['min' => 31, 'max' => 50, 'score' => 15],
            'high_risk' => ['min' => 51, 'max' => 65, 'score' => 25],
            'very_high_risk' => ['min' => 66, 'max' => 120, 'score' => 35]
        ],
        'bmi' => [
            'underweight' => ['min' => 0, 'max' => 18.5, 'score' => 10],
            'normal' => ['min' => 18.6, 'max' => 24.9, 'score' => 0],
            'overweight' => ['min' => 25, 'max' => 29.9, 'score' => 15],
            'obese' => ['min' => 30, 'max' => 50, 'score' => 25]
        ],
        'blood_pressure' => [
            'normal' => ['systolic_min' => 90, 'systolic_max' => 120, 'diastolic_min' => 60, 'diastolic_max' => 80, 'score' => 0],
            'elevated' => ['systolic_min' => 121, 'systolic_max' => 129, 'diastolic_min' => 60, 'diastolic_max' => 80, 'score' => 10],
            'stage1' => ['systolic_min' => 130, 'systolic_max' => 139, 'diastolic_min' => 81, 'diastolic_max' => 89, 'score' => 20],
            'stage2' => ['systolic_min' => 140, 'systolic_max' => 180, 'diastolic_min' => 90, 'diastolic_max' => 120, 'score' => 30],
            'crisis' => ['systolic_min' => 181, 'systolic_max' => 250, 'diastolic_min' => 121, 'diastolic_max' => 150, 'score' => 40]
        ],
        'heart_rate' => [
            'bradycardia' => ['min' => 0, 'max' => 59, 'score' => 15],
            'normal' => ['min' => 60, 'max' => 100, 'score' => 0],
            'tachycardia' => ['min' => 101, 'max' => 150, 'score' => 20],
            'severe_tachycardia' => ['min' => 151, 'max' => 200, 'score' => 30]
        ],
        'temperature' => [
            'hypothermia' => ['min' => 0, 'max' => 95, 'score' => 20],
            'normal' => ['min' => 95.1, 'max' => 100.4, 'score' => 0],
            'low_grade_fever' => ['min' => 100.5, 'max' => 102.2, 'score' => 10],
            'moderate_fever' => ['min' => 102.3, 'max' => 104, 'score' => 20],
            'high_fever' => ['min' => 104.1, 'max' => 106, 'score' => 30],
            'hyperpyrexia' => ['min' => 106.1, 'max' => 110, 'score' => 40]
        ],
        'oxygen_saturation' => [
            'critical' => ['min' => 0, 'max' => 88, 'score' => 40],
            'low' => ['min' => 89, 'max' => 94, 'score' => 20],
            'normal' => ['min' => 95, 'max' => 100, 'score' => 0]
        ]
    ];

    private $medicalHistoryRisks = [
        'diabetes' => 25,
        'heart_disease' => 30,
        'hypertension' => 20,
        'asthma' => 15,
        'copd' => 25,
        'kidney_disease' => 20,
        'liver_disease' => 20,
        'cancer' => 30,
        'stroke' => 35,
        'obesity' => 20,
        'smoking' => 25,
        'alcohol_abuse' => 20,
        'drug_abuse' => 30
    ];

    private $lifestyleRisks = [
        'smoker' => 25,
        'alcohol_consumer' => 15,
        'sedentary_lifestyle' => 20,
        'poor_diet' => 15,
        'high_stress' => 10,
        'poor_sleep' => 10
    ];

    public function calculateRiskScore($patientData, $vitals = [], $medicalHistory = []) {
        $riskScore = 0;
        $riskFactors = [];
        $recommendations = [];
        $urgencyLevel = 'low';

        // Age risk assessment
        if (isset($patientData['age'])) {
            $ageRisk = $this->calculateAgeRisk($patientData['age']);
            $riskScore += $ageRisk['score'];
            $riskFactors[] = $ageRisk;
        }

        // BMI risk assessment (if height and weight available)
        if (isset($patientData['height']) && isset($patientData['weight'])) {
            $bmi = $patientData['weight'] / pow($patientData['height'] / 100, 2);
            $bmiRisk = $this->calculateBMIRisk($bmi);
            $riskScore += $bmiRisk['score'];
            $riskFactors[] = $bmiRisk;
        }

        // Vitals risk assessment
        if (!empty($vitals)) {
            $vitalRisks = $this->calculateVitalsRisk($vitals);
            $riskScore += $vitalRisks['score'];
            $riskFactors = array_merge($riskFactors, $vitalRisks['factors']);
        }

        // Medical history risk assessment
        if (!empty($medicalHistory)) {
            $historyRisks = $this->calculateMedicalHistoryRisk($medicalHistory);
            $riskScore += $historyRisks['score'];
            $riskFactors = array_merge($riskFactors, $historyRisks['factors']);
        }

        // Lifestyle risk assessment
        if (isset($patientData['lifestyle'])) {
            $lifestyleRisks = $this->calculateLifestyleRisk($patientData['lifestyle']);
            $riskScore += $lifestyleRisks['score'];
            $riskFactors = array_merge($riskFactors, $lifestyleRisks['factors']);
        }

        // Cap the risk score at 100
        $riskScore = min($riskScore, 100);

        // Determine urgency level
        $urgencyLevel = $this->determineUrgencyLevel($riskScore, $riskFactors);

        // Generate recommendations
        $recommendations = $this->generateRiskRecommendations($riskScore, $riskFactors, $urgencyLevel);

        return [
            'risk_score' => round($riskScore, 2),
            'urgency_level' => $urgencyLevel,
            'risk_factors' => $riskFactors,
            'recommendations' => $recommendations,
            'assessment_date' => date('Y-m-d H:i:s'),
            'trend_analysis' => $this->analyzeTrends($vitals)
        ];
    }

    private function calculateAgeRisk($age) {
        foreach ($this->riskFactors['age'] as $category => $range) {
            if ($age >= $range['min'] && $age <= $range['max']) {
                return [
                    'factor' => 'Age',
                    'category' => $category,
                    'score' => $range['score'],
                    'description' => "Age {$age} years - {$category} risk category"
                ];
            }
        }
        return ['factor' => 'Age', 'category' => 'unknown', 'score' => 0, 'description' => 'Age data insufficient'];
    }

    private function calculateBMIRisk($bmi) {
        foreach ($this->riskFactors['bmi'] as $category => $range) {
            if ($bmi >= $range['min'] && $bmi <= $range['max']) {
                return [
                    'factor' => 'BMI',
                    'category' => $category,
                    'score' => $range['score'],
                    'value' => round($bmi, 1),
                    'description' => "BMI {$bmi} - {$category} category"
                ];
            }
        }
        return ['factor' => 'BMI', 'category' => 'unknown', 'score' => 0, 'description' => 'BMI calculation error'];
    }

    private function calculateVitalsRisk($vitals) {
        $totalScore = 0;
        $factors = [];

        // Blood pressure risk
        if (isset($vitals['blood_pressure'])) {
            $bpRisk = $this->calculateBloodPressureRisk($vitals['blood_pressure']);
            $totalScore += $bpRisk['score'];
            $factors[] = $bpRisk;
        }

        // Heart rate risk
        if (isset($vitals['heart_rate'])) {
            $hrRisk = $this->calculateHeartRateRisk($vitals['heart_rate']);
            $totalScore += $hrRisk['score'];
            $factors[] = $hrRisk;
        }

        // Temperature risk
        if (isset($vitals['temperature'])) {
            $tempRisk = $this->calculateTemperatureRisk($vitals['temperature']);
            $totalScore += $tempRisk['score'];
            $factors[] = $tempRisk;
        }

        // Oxygen saturation risk
        if (isset($vitals['oxygen_saturation'])) {
            $oxyRisk = $this->calculateOxygenRisk($vitals['oxygen_saturation']);
            $totalScore += $oxyRisk['score'];
            $factors[] = $oxyRisk;
        }

        return ['score' => $totalScore, 'factors' => $factors];
    }

    private function calculateBloodPressureRisk($bloodPressure) {
        // Parse blood pressure string (e.g., "120/80")
        $parts = explode('/', $bloodPressure);
        if (count($parts) === 2) {
            $systolic = (int)$parts[0];
            $diastolic = (int)$parts[1];

            foreach ($this->riskFactors['blood_pressure'] as $category => $range) {
                if ($systolic >= $range['systolic_min'] && $systolic <= $range['systolic_max'] &&
                    $diastolic >= $range['diastolic_min'] && $diastolic <= $range['diastolic_max']) {
                    return [
                        'factor' => 'Blood Pressure',
                        'category' => $category,
                        'score' => $range['score'],
                        'value' => $bloodPressure,
                        'description' => "BP {$bloodPressure} - {$category} category"
                    ];
                }
            }
        }

        return ['factor' => 'Blood Pressure', 'category' => 'unknown', 'score' => 0, 'description' => 'Invalid BP format'];
    }

    private function calculateHeartRateRisk($heartRate) {
        $hr = (int)$heartRate;
        foreach ($this->riskFactors['heart_rate'] as $category => $range) {
            if ($hr >= $range['min'] && $hr <= $range['max']) {
                return [
                    'factor' => 'Heart Rate',
                    'category' => $category,
                    'score' => $range['score'],
                    'value' => $heartRate . ' bpm',
                    'description' => "Heart Rate {$heartRate} bpm - {$category} category"
                ];
            }
        }
        return ['factor' => 'Heart Rate', 'category' => 'unknown', 'score' => 0, 'description' => 'Invalid heart rate'];
    }

    private function calculateTemperatureRisk($temperature) {
        $temp = (float)$temperature;
        foreach ($this->riskFactors['temperature'] as $category => $range) {
            if ($temp >= $range['min'] && $temp <= $range['max']) {
                return [
                    'factor' => 'Temperature',
                    'category' => $category,
                    'score' => $range['score'],
                    'value' => $temperature . '°F',
                    'description' => "Temperature {$temperature}°F - {$category} category"
                ];
            }
        }
        return ['factor' => 'Temperature', 'category' => 'unknown', 'score' => 0, 'description' => 'Invalid temperature'];
    }

    private function calculateOxygenRisk($oxygenSaturation) {
        $oxy = (int)$oxygenSaturation;
        foreach ($this->riskFactors['oxygen_saturation'] as $category => $range) {
            if ($oxy >= $range['min'] && $oxy <= $range['max']) {
                return [
                    'factor' => 'Oxygen Saturation',
                    'category' => $category,
                    'score' => $range['score'],
                    'value' => $oxygenSaturation . '%',
                    'description' => "Oxygen Saturation {$oxygenSaturation}% - {$category} category"
                ];
            }
        }
        return ['factor' => 'Oxygen Saturation', 'category' => 'unknown', 'score' => 0, 'description' => 'Invalid oxygen saturation'];
    }

    private function calculateMedicalHistoryRisk($medicalHistory) {
        $totalScore = 0;
        $factors = [];

        foreach ($medicalHistory as $condition) {
            $condition = strtolower(trim($condition));
            if (isset($this->medicalHistoryRisks[$condition])) {
                $score = $this->medicalHistoryRisks[$condition];
                $totalScore += $score;
                $factors[] = [
                    'factor' => 'Medical History',
                    'category' => 'chronic_condition',
                    'condition' => $condition,
                    'score' => $score,
                    'description' => "History of {$condition} increases risk"
                ];
            }
        }

        return ['score' => $totalScore, 'factors' => $factors];
    }

    private function calculateLifestyleRisk($lifestyle) {
        $totalScore = 0;
        $factors = [];

        foreach ($lifestyle as $factor) {
            $factor = strtolower(trim($factor));
            if (isset($this->lifestyleRisks[$factor])) {
                $score = $this->lifestyleRisks[$factor];
                $totalScore += $score;
                $factors[] = [
                    'factor' => 'Lifestyle',
                    'category' => 'behavioral',
                    'condition' => $factor,
                    'score' => $score,
                    'description' => "{$factor} increases health risk"
                ];
            }
        }

        return ['score' => $totalScore, 'factors' => $factors];
    }

    private function determineUrgencyLevel($riskScore, $riskFactors) {
        // Check for critical factors
        $criticalFactors = array_filter($riskFactors, function($factor) {
            return isset($factor['category']) && 
                   in_array($factor['category'], ['crisis', 'critical', 'severe_tachycardia', 'hyperpyrexia']);
        });

        if (!empty($criticalFactors)) {
            return 'critical';
        }

        // Determine based on overall score
        if ($riskScore >= 70) {
            return 'high';
        } elseif ($riskScore >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    private function generateRiskRecommendations($riskScore, $riskFactors, $urgencyLevel) {
        $recommendations = [];

        // Base recommendations by urgency level
        switch ($urgencyLevel) {
            case 'critical':
                $recommendations[] = "🚨 Immediate medical attention required - Emergency room visit recommended";
                $recommendations[] = "Call emergency services or go to nearest emergency department";
                break;
            case 'high':
                $recommendations[] = "⚠️ Urgent medical consultation needed within 24 hours";
                $recommendations[] = "Schedule appointment with primary care physician immediately";
                break;
            case 'medium':
                $recommendations[] = "📅 Medical consultation recommended within 1 week";
                $recommendations[] = "Schedule routine check-up with healthcare provider";
                break;
            default:
                $recommendations[] = "✅ Continue regular health monitoring";
                $recommendations[] = "Schedule routine annual check-up";
        }

        // Specific recommendations based on risk factors
        foreach ($riskFactors as $factor) {
            switch ($factor['factor']) {
                case 'Blood Pressure':
                    if ($factor['score'] > 15) {
                        $recommendations[] = "🩺 Monitor blood pressure daily and consult cardiologist";
                        $recommendations[] = "Reduce sodium intake and increase physical activity";
                    }
                    break;
                case 'BMI':
                    if ($factor['category'] === 'overweight' || $factor['category'] === 'obese') {
                        $recommendations[] = "⚖️ Consult nutritionist for weight management plan";
                        $recommendations[] = "Implement regular exercise routine and balanced diet";
                    }
                    break;
                case 'Medical History':
                    $recommendations[] = "📋 Ensure regular follow-ups for chronic conditions";
                    $recommendations[] = "Adhere strictly to prescribed medications";
                    break;
                case 'Lifestyle':
                    if (isset($factor['condition']) && $factor['condition'] === 'smoker') {
                        $recommendations[] = "🚭 Smoking cessation program highly recommended";
                    }
                    break;
            }
        }

        return array_unique($recommendations);
    }

    private function analyzeTrends($vitals) {
        $trends = [];
        
        // This would ideally compare with historical data
        // For now, we'll provide basic trend analysis based on current values
        
        if (isset($vitals['blood_pressure'])) {
            $parts = explode('/', $vitals['blood_pressure']);
            if (count($parts) === 2) {
                $systolic = (int)$parts[0];
                $diastolic = (int)$parts[1];
                
                if ($systolic > 140 || $diastolic > 90) {
                    $trends[] = "Blood pressure trending upward - requires monitoring";
                }
            }
        }

        if (isset($vitals['heart_rate'])) {
            $hr = (int)$vitals['heart_rate'];
            if ($hr > 100) {
                $trends[] = "Heart rate elevated - may indicate stress or underlying condition";
            } elseif ($hr < 60) {
                $trends[] = "Heart rate low - may indicate fitness or underlying condition";
            }
        }

        return $trends;
    }

    public function getRiskColor($riskScore) {
        if ($riskScore >= 70) return '#dc3545'; // Red
        if ($riskScore >= 40) return '#fd7e14'; // Orange
        if ($riskScore >= 20) return '#ffc107'; // Yellow
        return '#28a745'; // Green
    }

    public function getRiskLevelText($riskScore) {
        if ($riskScore >= 70) return 'High Risk';
        if ($riskScore >= 40) return 'Moderate Risk';
        if ($riskScore >= 20) return 'Low Risk';
        return 'Very Low Risk';
    }
}

// API endpoint for risk assessment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calculate_risk') {
    include "../db.php";
    
    $patientData = json_decode($_POST['patient_data'] ?? '{}', true);
    $vitals = json_decode($_POST['vitals'] ?? '{}', true);
    $medicalHistory = json_decode($_POST['medical_history'] ?? '[]', true);
    
    $engine = new RiskEngine();
    $results = $engine->calculateRiskScore($patientData, $vitals, $medicalHistory);
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Legacy functions for backward compatibility
function calculateRisk($severity, $status) {
    // Convert numeric severity to string if needed
    if (is_numeric($severity)) {
        $severity_num = (int)$severity;
        if ($severity_num >= 4) return "High Risk";
        if ($severity_num >= 3) return "Medium Risk";
        return "Low Risk";
    }
    
    // Handle string severity values
    if ($status === "Critical") return "High Risk";
    if ($severity === "Severe") return "Medium Risk";
    return "Low Risk";
}

function nextAction($risk) {
    if ($risk === "High Risk") return "Immediate hospital visit required";
    if ($risk === "Medium Risk") return "Consult specialist within 7 days";
    return "Monitor health and follow lifestyle advice";
}
?>
