<?php
/**
 * AI Summary Engine
 * Rule-based AI system for generating intelligent patient summaries and insights
 */

class AISummaryEngine {
    private $vitalPatterns = [
        'blood_pressure' => [
            'normal' => ['min_systolic' => 90, 'max_systolic' => 120, 'min_diastolic' => 60, 'max_diastolic' => 80],
            'elevated' => ['min_systolic' => 121, 'max_systolic' => 129, 'min_diastolic' => 60, 'max_diastolic' => 80],
            'high_stage1' => ['min_systolic' => 130, 'max_systolic' => 139, 'min_diastolic' => 81, 'max_diastolic' => 89],
            'high_stage2' => ['min_systolic' => 140, 'max_systolic' => 180, 'min_diastolic' => 90, 'max_diastolic' => 120],
            'crisis' => ['min_systolic' => 181, 'max_systolic' => 250, 'min_diastolic' => 121, 'max_diastolic' => 150]
        ],
        'heart_rate' => [
            'bradycardia' => ['min' => 0, 'max' => 59],
            'normal' => ['min' => 60, 'max' => 100],
            'tachycardia' => ['min' => 101, 'max' => 150],
            'severe_tachycardia' => ['min' => 151, 'max' => 200]
        ],
        'temperature' => [
            'hypothermia' => ['min' => 0, 'max' => 95],
            'normal' => ['min' => 95.1, 'max' => 100.4],
            'low_grade_fever' => ['min' => 100.5, 'max' => 102.2],
            'moderate_fever' => ['min' => 102.3, 'max' => 104],
            'high_fever' => ['min' => 104.1, 'max' => 106],
            'hyperpyrexia' => ['min' => 106.1, 'max' => 110]
        ],
        'oxygen_saturation' => [
            'critical' => ['min' => 0, 'max' => 88],
            'low' => ['min' => 89, 'max' => 94],
            'normal' => ['min' => 95, 'max' => 100]
        ]
    ];

    private $trendThresholds = [
        'blood_pressure_increase' => 10, // mmHg increase considered significant
        'heart_rate_increase' => 20, // bpm increase considered significant
        'temperature_increase' => 2, // Fahrenheit increase considered significant
        'oxygen_decrease' => 3 // percentage decrease considered significant
    ];

    private $riskKeywords = [
        'critical' => ['critical', 'emergency', 'severe', 'life-threatening', 'urgent'],
        'high' => ['high', 'elevated', 'increased', 'abnormal', 'concerning'],
        'medium' => ['moderate', 'borderline', 'slightly elevated', 'mild'],
        'low' => ['normal', 'stable', 'within limits', 'controlled']
    ];

    public function generatePatientSummary($patientData, $vitalsHistory = [], $medicalHistory = [], $doctorNotes = []) {
        $summary = [
            'overall_status' => 'stable',
            'key_findings' => [],
            'trend_analysis' => [],
            'risk_assessment' => 'low',
            'recommendations' => [],
            'priority_level' => 'routine',
            'summary_text' => ''
        ];

        // Analyze current vitals
        if (!empty($vitalsHistory)) {
            $currentVitals = $vitalsHistory[0]; // Assuming first is most recent
            $vitalAnalysis = $this->analyzeCurrentVitals($currentVitals);
            $summary['key_findings'] = array_merge($summary['key_findings'], $vitalAnalysis['findings']);
            $summary['risk_assessment'] = $this->getHigherRisk($summary['risk_assessment'], $vitalAnalysis['risk']);
        }

        // Analyze trends
        if (count($vitalsHistory) > 1) {
            $trendAnalysis = $this->analyzeVitalsTrend($vitalsHistory);
            $summary['trend_analysis'] = $trendAnalysis;
            if ($trendAnalysis['overall_trend'] === 'worsening') {
                $summary['risk_assessment'] = $this->getHigherRisk($summary['risk_assessment'], 'medium');
            }
        }

        // Analyze medical history
        if (!empty($medicalHistory)) {
            $historyAnalysis = $this->analyzeMedicalHistory($medicalHistory);
            $summary['key_findings'] = array_merge($summary['key_findings'], $historyAnalysis['findings']);
            $summary['risk_assessment'] = $this->getHigherRisk($summary['risk_assessment'], $historyAnalysis['risk']);
        }

        // Analyze doctor notes
        if (!empty($doctorNotes)) {
            $notesAnalysis = $this->analyzeDoctorNotes($doctorNotes);
            $summary['key_findings'] = array_merge($summary['key_findings'], $notesAnalysis['findings']);
            $summary['risk_assessment'] = $this->getHigherRisk($summary['risk_assessment'], $notesAnalysis['risk']);
        }

        // Determine overall status and priority
        $summary['overall_status'] = $this->determineOverallStatus($summary['risk_assessment'], $summary['trend_analysis']);
        $summary['priority_level'] = $this->determinePriorityLevel($summary['risk_assessment'], $summary['overall_status']);

        // Generate recommendations
        $summary['recommendations'] = $this->generateSummaryRecommendations($summary);

        // Generate summary text
        $summary['summary_text'] = $this->generateSummaryText($summary, $patientData);

        return $summary;
    }

    private function analyzeCurrentVitals($vitals) {
        $findings = [];
        $riskLevel = 'low';

        // Blood pressure analysis
        if (isset($vitals['blood_pressure'])) {
            $bpAnalysis = $this->analyzeBloodPressure($vitals['blood_pressure']);
            $findings[] = $bpAnalysis['description'];
            $riskLevel = $this->getHigherRisk($riskLevel, $bpAnalysis['risk']);
        }

        // Heart rate analysis
        if (isset($vitals['heart_rate'])) {
            $hrAnalysis = $this->analyzeHeartRate($vitals['heart_rate']);
            $findings[] = $hrAnalysis['description'];
            $riskLevel = $this->getHigherRisk($riskLevel, $hrAnalysis['risk']);
        }

        // Temperature analysis
        if (isset($vitals['temperature'])) {
            $tempAnalysis = $this->analyzeTemperature($vitals['temperature']);
            $findings[] = $tempAnalysis['description'];
            $riskLevel = $this->getHigherRisk($riskLevel, $tempAnalysis['risk']);
        }

        // Oxygen saturation analysis
        if (isset($vitals['oxygen_saturation'])) {
            $oxyAnalysis = $this->analyzeOxygenSaturation($vitals['oxygen_saturation']);
            $findings[] = $oxyAnalysis['description'];
            $riskLevel = $this->getHigherRisk($riskLevel, $oxyAnalysis['risk']);
        }

        return ['findings' => $findings, 'risk' => $riskLevel];
    }

    private function analyzeBloodPressure($bloodPressure) {
        $parts = explode('/', $bloodPressure);
        if (count($parts) !== 2) {
            return ['description' => 'Blood pressure reading invalid', 'risk' => 'medium'];
        }

        $systolic = (int)$parts[0];
        $diastolic = (int)$parts[1];

        foreach ($this->vitalPatterns['blood_pressure'] as $category => $range) {
            if ($systolic >= $range['min_systolic'] && $systolic <= $range['max_systolic'] &&
                $diastolic >= $range['min_diastolic'] && $diastolic <= $range['max_diastolic']) {
                
                $risk = $this->getRiskFromCategory($category);
                $description = "Blood pressure is {$category} ({$bloodPressure})";
                
                if ($risk !== 'low') {
                    $description .= " - requires monitoring";
                }
                
                return ['description' => $description, 'risk' => $risk];
            }
        }

        return ['description' => "Blood pressure reading unclear ({$bloodPressure})", 'risk' => 'medium'];
    }

    private function analyzeHeartRate($heartRate) {
        $hr = (int)$heartRate;
        
        foreach ($this->vitalPatterns['heart_rate'] as $category => $range) {
            if ($hr >= $range['min'] && $hr <= $range['max']) {
                $risk = $this->getRiskFromCategory($category);
                $description = "Heart rate is {$category} ({$heartRate} bpm)";
                
                if ($risk !== 'low') {
                    $description .= " - requires attention";
                }
                
                return ['description' => $description, 'risk' => $risk];
            }
        }

        return ['description' => "Heart rate reading unclear ({$heartRate} bpm)", 'risk' => 'medium'];
    }

    private function analyzeTemperature($temperature) {
        $temp = (float)$temperature;
        
        foreach ($this->vitalPatterns['temperature'] as $category => $range) {
            if ($temp >= $range['min'] && $temp <= $range['max']) {
                $risk = $this->getRiskFromCategory($category);
                $description = "Temperature is {$category} ({$temperature}°F)";
                
                if (strpos($category, 'fever') !== false) {
                    $description .= " - fever detected";
                }
                
                return ['description' => $description, 'risk' => $risk];
            }
        }

        return ['description' => "Temperature reading unclear ({$temperature}°F)", 'risk' => 'medium'];
    }

    private function analyzeOxygenSaturation($oxygenSaturation) {
        $oxy = (int)$oxygenSaturation;
        
        foreach ($this->vitalPatterns['oxygen_saturation'] as $category => $range) {
            if ($oxy >= $range['min'] && $oxy <= $range['max']) {
                $risk = $this->getRiskFromCategory($category);
                $description = "Oxygen saturation is {$category} ({$oxygenSaturation}%)";
                
                if ($category === 'critical' || $category === 'low') {
                    $description .= " - immediate attention required";
                }
                
                return ['description' => $description, 'risk' => $risk];
            }
        }

        return ['description' => "Oxygen saturation reading unclear ({$oxygenSaturation}%)", 'risk' => 'medium'];
    }

    private function analyzeVitalsTrend($vitalsHistory) {
        $trends = [];
        $overallTrend = 'stable';

        if (count($vitalsHistory) < 2) {
            return ['trends' => [], 'overall_trend' => 'insufficient_data'];
        }

        $latest = $vitalsHistory[0];
        $previous = $vitalsHistory[1];

        // Blood pressure trend
        if (isset($latest['blood_pressure']) && isset($previous['blood_pressure'])) {
            $latestParts = explode('/', $latest['blood_pressure']);
            $previousParts = explode('/', $previous['blood_pressure']);
            
            if (count($latestParts) === 2 && count($previousParts) === 2) {
                $systolicChange = (int)$latestParts[0] - (int)$previousParts[0];
                $diastolicChange = (int)$latestParts[1] - (int)$previousParts[1];
                
                if (abs($systolicChange) >= $this->trendThresholds['blood_pressure_increase'] ||
                    abs($diastolicChange) >= $this->trendThresholds['blood_pressure_increase']) {
                    
                    $direction = $systolicChange > 0 ? 'increasing' : 'decreasing';
                    $trends[] = "Blood pressure is {$direction}";
                    if ($systolicChange > 0) $overallTrend = 'worsening';
                }
            }
        }

        // Heart rate trend
        if (isset($latest['heart_rate']) && isset($previous['heart_rate'])) {
            $hrChange = (int)$latest['heart_rate'] - (int)$previous['heart_rate'];
            
            if (abs($hrChange) >= $this->trendThresholds['heart_rate_increase']) {
                $direction = $hrChange > 0 ? 'increasing' : 'decreasing';
                $trends[] = "Heart rate is {$direction}";
                if ($hrChange > 0) $overallTrend = 'worsening';
            }
        }

        // Temperature trend
        if (isset($latest['temperature']) && isset($previous['temperature'])) {
            $tempChange = (float)$latest['temperature'] - (float)$previous['temperature'];
            
            if (abs($tempChange) >= $this->trendThresholds['temperature_increase']) {
                $direction = $tempChange > 0 ? 'increasing' : 'decreasing';
                $trends[] = "Temperature is {$direction}";
                if ($tempChange > 0) $overallTrend = 'worsening';
            }
        }

        // Oxygen saturation trend
        if (isset($latest['oxygen_saturation']) && isset($previous['oxygen_saturation'])) {
            $oxyChange = (int)$latest['oxygen_saturation'] - (int)$previous['oxygen_saturation'];
            
            if (abs($oxyChange) >= $this->trendThresholds['oxygen_decrease']) {
                $direction = $oxyChange < 0 ? 'decreasing' : 'increasing';
                $trends[] = "Oxygen saturation is {$direction}";
                if ($oxyChange < 0) $overallTrend = 'worsening';
            }
        }

        return ['trends' => $trends, 'overall_trend' => $overallTrend];
    }

    private function analyzeMedicalHistory($medicalHistory) {
        $findings = [];
        $riskLevel = 'low';
        $criticalConditions = ['heart_disease', 'cancer', 'stroke', 'kidney_disease', 'liver_disease'];
        $chronicConditions = ['diabetes', 'hypertension', 'asthma', 'copd'];

        foreach ($medicalHistory as $condition) {
            $conditionName = strtolower($condition['disease_name'] ?? $condition);
            
            if (in_array($conditionName, $criticalConditions)) {
                $findings[] = "History of critical condition: {$conditionName}";
                $riskLevel = 'high';
            } elseif (in_array($conditionName, $chronicConditions)) {
                $findings[] = "History of chronic condition: {$conditionName}";
                $riskLevel = $this->getHigherRisk($riskLevel, 'medium');
            }
        }

        if (!empty($findings)) {
            $findings[] = "Multiple medical conditions require ongoing management";
        }

        return ['findings' => $findings, 'risk' => $riskLevel];
    }

    private function analyzeDoctorNotes($doctorNotes) {
        $findings = [];
        $riskLevel = 'low';
        $recentNotes = array_slice($doctorNotes, 0, 3); // Last 3 notes

        foreach ($recentNotes as $note) {
            $diagnosis = strtolower($note['diagnosis'] ?? '');
            $severity = strtolower($note['severity'] ?? '');
            
            // Check for critical keywords in diagnosis
            foreach ($this->riskKeywords['critical'] as $keyword) {
                if (strpos($diagnosis, $keyword) !== false) {
                    $findings[] = "Recent critical diagnosis noted";
                    $riskLevel = 'critical';
                    break 2;
                }
            }

            // Check severity level
            if ($severity === 'critical') {
                $findings[] = "Critical severity level in recent notes";
                $riskLevel = 'critical';
            } elseif ($severity === 'high') {
                $findings[] = "High severity level in recent notes";
                $riskLevel = $this->getHigherRisk($riskLevel, 'high');
            }
        }

        return ['findings' => $findings, 'risk' => $riskLevel];
    }

    private function getRiskFromCategory($category) {
        if (strpos($category, 'crisis') !== false || strpos($category, 'critical') !== false || 
            strpos($category, 'severe') !== false) {
            return 'critical';
        } elseif (strpos($category, 'high') !== false || strpos($category, 'tachycardia') !== false || 
                  strpos($category, 'fever') !== false) {
            return 'high';
        } elseif (strpos($category, 'elevated') !== false || strpos($category, 'low') !== false) {
            return 'medium';
        }
        return 'low';
    }

    private function getHigherRisk($currentRisk, $newRisk) {
        $riskLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        return $riskLevels[$newRisk] > $riskLevels[$currentRisk] ? $newRisk : $currentRisk;
    }

    private function determineOverallStatus($riskAssessment, $trendAnalysis) {
        if ($riskAssessment === 'critical') {
            return 'critical';
        } elseif ($riskAssessment === 'high') {
            return 'serious';
        } elseif ($riskAssessment === 'medium' || 
                 (isset($trendAnalysis['overall_trend']) && $trendAnalysis['overall_trend'] === 'worsening')) {
            return 'concerning';
        }
        return 'stable';
    }

    private function determinePriorityLevel($riskAssessment, $overallStatus) {
        if ($riskAssessment === 'critical' || $overallStatus === 'critical') {
            return 'emergency';
        } elseif ($riskAssessment === 'high' || $overallStatus === 'serious') {
            return 'urgent';
        } elseif ($riskAssessment === 'medium' || $overallStatus === 'concerning') {
            return 'priority';
        }
        return 'routine';
    }

    private function generateSummaryRecommendations($summary) {
        $recommendations = [];

        switch ($summary['priority_level']) {
            case 'emergency':
                $recommendations[] = "🚨 Immediate medical attention required";
                $recommendations[] = "Call emergency services or go to emergency department";
                break;
            case 'urgent':
                $recommendations[] = "⚠️ Urgent medical consultation needed within 24 hours";
                $recommendations[] = "Contact primary care physician immediately";
                break;
            case 'priority':
                $recommendations[] = "📅 Medical consultation recommended within 3-5 days";
                $recommendations[] = "Schedule appointment with healthcare provider";
                break;
            default:
                $recommendations[] = "✅ Continue routine monitoring";
                $recommendations[] = "Schedule regular check-up as needed";
        }

        // Add trend-specific recommendations
        if (isset($summary['trend_analysis']['overall_trend'])) {
            if ($summary['trend_analysis']['overall_trend'] === 'worsening') {
                $recommendations[] = "📈 Vital signs trending downward - increased monitoring required";
            }
        }

        return $recommendations;
    }

    private function generateSummaryText($summary, $patientData) {
        $patientName = $patientData['name'] ?? 'Patient';
        $text = "Patient Summary for {$patientName}:\n\n";
        
        $text .= "Overall Status: " . ucfirst($summary['overall_status']) . "\n";
        $text .= "Priority Level: " . ucfirst($summary['priority_level']) . "\n";
        $text .= "Risk Assessment: " . ucfirst($summary['risk_assessment']) . "\n\n";

        if (!empty($summary['key_findings'])) {
            $text .= "Key Findings:\n";
            foreach ($summary['key_findings'] as $finding) {
                $text .= "• " . ucfirst($finding) . "\n";
            }
            $text .= "\n";
        }

        if (!empty($summary['trend_analysis']['trends'])) {
            $text .= "Trend Analysis:\n";
            foreach ($summary['trend_analysis']['trends'] as $trend) {
                $text .= "• " . ucfirst($trend) . "\n";
            }
            $text .= "\n";
        }

        if (!empty($summary['recommendations'])) {
            $text .= "Recommendations:\n";
            foreach ($summary['recommendations'] as $recommendation) {
                $text .= "• " . $recommendation . "\n";
            }
        }

        return $text;
    }

    public function generateQuickInsight($vitals, $patientAge = null) {
        $insights = [];
        $alerts = [];

        // Quick vital analysis
        if (isset($vitals['blood_pressure'])) {
            $bpAnalysis = $this->analyzeBloodPressure($vitals['blood_pressure']);
            if ($bpAnalysis['risk'] !== 'low') {
                $insights[] = $bpAnalysis['description'];
                if ($bpAnalysis['risk'] === 'critical') {
                    $alerts[] = "Critical blood pressure detected";
                }
            }
        }

        if (isset($vitals['heart_rate'])) {
            $hrAnalysis = $this->analyzeHeartRate($vitals['heart_rate']);
            if ($hrAnalysis['risk'] !== 'low') {
                $insights[] = $hrAnalysis['description'];
                if ($hrAnalysis['risk'] === 'critical') {
                    $alerts[] = "Critical heart rate detected";
                }
            }
        }

        // Age-adjusted insights
        if ($patientAge) {
            if ($patientAge > 65 && isset($vitals['blood_pressure'])) {
                $parts = explode('/', $vitals['blood_pressure']);
                if (count($parts) === 2 && (int)$parts[0] > 130) {
                    $insights[] = "Elevated blood pressure in elderly patient requires careful monitoring";
                }
            }
        }

        return [
            'insights' => $insights,
            'alerts' => $alerts,
            'overall_risk' => empty($alerts) ? 'low' : (empty($insights) ? 'medium' : 'high')
        ];
    }
}

// API endpoint for AI summary
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_summary') {
    include "../db.php";
    
    $patientData = json_decode($_POST['patient_data'] ?? '{}', true);
    $vitalsHistory = json_decode($_POST['vitals_history'] ?? '[]', true);
    $medicalHistory = json_decode($_POST['medical_history'] ?? '[]', true);
    $doctorNotes = json_decode($_POST['doctor_notes'] ?? '[]', true);
    
    $engine = new AISummaryEngine();
    $results = $engine->generatePatientSummary($patientData, $vitalsHistory, $medicalHistory, $doctorNotes);
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Legacy function for backward compatibility
function generateHealthSummary(array $history, string $city = "India"): string
{
    if (empty($history)) {
        return "No medical history available for analysis.";
    }

    $conditions = [];
    $severe = false;

    foreach ($history as $h) {
        if (!empty($h['disease_name'])) {
            $conditions[] = $h['disease_name'];
        }

        if (
            isset($h['severity_level']) &&
            strtolower(trim($h['severity_level'])) === 'high'
        ) {
            $severe = true;
        }
    }

    $conditionsText = implode(", ", array_unique($conditions));

    $summary  = "<strong>Overall Health Summary:</strong><br>";
    $summary .= "The patient has a medical history including <b>$conditionsText</b>. ";

    $summary .= $severe
        ? "Some conditions appear severe and require close medical supervision.<br><br>"
        : "Most conditions appear manageable with regular medical care.<br><br>";

    $summary .= "<strong>Risk Assessment:</strong><br>";
    $summary .= $severe
        ? "The patient is at a moderate to high health risk if symptoms are ignored.<br><br>"
        : "The patient is at a low to moderate health risk.<br><br>";

    $summary .= "<strong>Lifestyle Advice:</strong><br>";
    $summary .= "• Maintain a balanced diet<br>";
    $summary .= "• Exercise regularly<br>";
    $summary .= "• Follow prescribed medication schedules<br>";
    $summary .= "• Avoid stress, smoking, and alcohol<br><br>";

    $summary .= "<strong>Recommended Hospitals in $city:</strong><br>";
    $summary .= "• Multi-specialty hospitals<br>";
    $summary .= "• Government medical colleges<br>";
    $summary .= "• Well-rated private clinics<br><br>";

    $summary .= "<strong>Recommended Doctor Specialties:</strong><br>";
    $summary .= "• General Physician<br>";
    $summary .= "• Relevant specialists based on condition";

    return $summary;
}
?>
