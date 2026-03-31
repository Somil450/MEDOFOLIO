<?php
/**
 * Disease Prediction Engine
 * Rule-based AI system for predicting possible diseases based on symptoms
 */

class DiseaseEngine {
    private $symptomsDatabase = [
        'fever' => [
            'diseases' => ['Common Cold', 'Flu', 'COVID-19', 'Dengue', 'Malaria', 'Typhoid', 'Pneumonia'],
            'weights' => [0.3, 0.4, 0.5, 0.6, 0.4, 0.5, 0.3],
            'specialists' => ['General Physician', 'Infectious Disease Specialist']
        ],
        'cough' => [
            'diseases' => ['Common Cold', 'Flu', 'COVID-19', 'Bronchitis', 'Pneumonia', 'Asthma', 'Allergies'],
            'weights' => [0.4, 0.5, 0.6, 0.7, 0.5, 0.3, 0.4],
            'specialists' => ['Pulmonologist', 'General Physician']
        ],
        'headache' => [
            'diseases' => ['Migraine', 'Tension Headache', 'Sinusitis', 'COVID-19', 'Hypertension', 'Meningitis'],
            'weights' => [0.7, 0.6, 0.5, 0.3, 0.4, 0.2],
            'specialists' => ['Neurologist', 'General Physician']
        ],
        'chest_pain' => [
            'diseases' => ['Heart Attack', 'Angina', 'Pneumonia', 'COVID-19', 'Anxiety', 'GERD'],
            'weights' => [0.8, 0.7, 0.4, 0.3, 0.2, 0.3],
            'specialists' => ['Cardiologist', 'Emergency Medicine']
        ],
        'shortness_of_breath' => [
            'diseases' => ['COVID-19', 'Asthma', 'Pneumonia', 'Heart Failure', 'Anxiety', 'Anemia'],
            'weights' => [0.7, 0.6, 0.6, 0.5, 0.3, 0.3],
            'specialists' => ['Pulmonologist', 'Cardiologist']
        ],
        'fatigue' => [
            'diseases' => ['Anemia', 'Thyroid Disorders', 'Depression', 'COVID-19', 'Diabetes', 'Chronic Fatigue Syndrome'],
            'weights' => [0.5, 0.4, 0.4, 0.3, 0.3, 0.6],
            'specialists' => ['General Physician', 'Endocrinologist']
        ],
        'nausea' => [
            'diseases' => ['Food Poisoning', 'Gastroenteritis', 'Migraine', 'Pregnancy', 'Appendicitis', 'COVID-19'],
            'weights' => [0.7, 0.6, 0.3, 0.4, 0.5, 0.3],
            'specialists' => ['Gastroenterologist', 'General Physician']
        ],
        'vomiting' => [
            'diseases' => ['Food Poisoning', 'Gastroenteritis', 'Migraine', 'Appendicitis', 'Kidney Stones', 'COVID-19'],
            'weights' => [0.7, 0.6, 0.3, 0.5, 0.4, 0.3],
            'specialists' => ['Gastroenterologist', 'General Physician']
        ],
        'diarrhea' => [
            'diseases' => ['Food Poisoning', 'Gastroenteritis', 'IBS', 'Crohn\'s Disease', 'COVID-19', 'Ulcerative Colitis'],
            'weights' => [0.7, 0.6, 0.4, 0.3, 0.3, 0.3],
            'specialists' => ['Gastroenterologist', 'General Physician']
        ],
        'abdominal_pain' => [
            'diseases' => ['Appendicitis', 'Gastritis', 'Kidney Stones', 'Pancreatitis', 'IBS', 'Ectopic Pregnancy'],
            'weights' => [0.6, 0.5, 0.5, 0.4, 0.4, 0.3],
            'specialists' => ['Gastroenterologist', 'General Surgeon']
        ],
        'dizziness' => [
            'diseases' => ['Vertigo', 'Anemia', 'Hypotension', 'Dehydration', 'Heart Problems', 'Anxiety'],
            'weights' => [0.6, 0.4, 0.5, 0.4, 0.3, 0.3],
            'specialists' => ['Neurologist', 'Cardiologist']
        ],
        'sore_throat' => [
            'diseases' => ['Strep Throat', 'Common Cold', 'Flu', 'COVID-19', 'Tonsillitis', 'Allergies'],
            'weights' => [0.7, 0.5, 0.4, 0.4, 0.5, 0.3],
            'specialists' => ['ENT Specialist', 'General Physician']
        ],
        'body_ache' => [
            'diseases' => ['Flu', 'COVID-19', 'Dengue', 'Fibromyalgia', 'Arthritis', 'Chronic Fatigue Syndrome'],
            'weights' => [0.7, 0.6, 0.6, 0.4, 0.3, 0.3],
            'specialists' => ['General Physician', 'Rheumatologist']
        ],
        'loss_of_taste' => [
            'diseases' => ['COVID-19', 'Common Cold', 'Sinusitis', 'Nutritional Deficiencies', 'Smoking'],
            'weights' => [0.8, 0.3, 0.3, 0.2, 0.2],
            'specialists' => ['ENT Specialist', 'General Physician']
        ],
        'loss_of_smell' => [
            'diseases' => ['COVID-19', 'Common Cold', 'Sinusitis', 'Nutritional Deficiencies', 'Smoking'],
            'weights' => [0.8, 0.3, 0.3, 0.2, 0.2],
            'specialists' => ['ENT Specialist', 'General Physician']
        ]
    ];

    private $diseasePatterns = [
        'COVID-19' => [
            'symptoms' => ['fever', 'cough', 'shortness_of_breath', 'fatigue', 'loss_of_taste', 'loss_of_smell', 'body_ache'],
            'min_symptoms' => 2,
            'critical_symptoms' => ['shortness_of_breath', 'chest_pain'],
            'specialist' => 'Infectious Disease Specialist'
        ],
        'Flu' => [
            'symptoms' => ['fever', 'cough', 'body_ache', 'headache', 'fatigue'],
            'min_symptoms' => 3,
            'critical_symptoms' => ['shortness_of_breath'],
            'specialist' => 'General Physician'
        ],
        'Dengue' => [
            'symptoms' => ['fever', 'headache', 'body_ache', 'fatigue'],
            'min_symptoms' => 3,
            'critical_symptoms' => ['abdominal_pain', 'vomiting'],
            'specialist' => 'Infectious Disease Specialist'
        ],
        'Heart Attack' => [
            'symptoms' => ['chest_pain', 'shortness_of_breath', 'fatigue', 'dizziness'],
            'min_symptoms' => 2,
            'critical_symptoms' => ['chest_pain', 'shortness_of_breath'],
            'specialist' => 'Cardiologist'
        ],
        'Pneumonia' => [
            'symptoms' => ['fever', 'cough', 'shortness_of_breath', 'chest_pain', 'fatigue'],
            'min_symptoms' => 3,
            'critical_symptoms' => ['shortness_of_breath', 'chest_pain'],
            'specialist' => 'Pulmonologist'
        ]
    ];

    public function analyzeSymptoms($symptoms, $age = null, $gender = null) {
        $symptoms = is_array($symptoms) ? $symptoms : [$symptoms];
        $results = [
            'possible_diseases' => [],
            'urgency_level' => 'low',
            'recommended_specialist' => 'General Physician',
            'confidence_scores' => [],
            'critical_symptoms' => [],
            'recommendations' => []
        ];

        // Calculate disease probabilities
        $diseaseScores = [];
        $criticalSymptomsDetected = [];

        foreach ($symptoms as $symptom) {
            $symptom = strtolower(trim($symptom));
            if (isset($this->symptomsDatabase[$symptom])) {
                $data = $this->symptomsDatabase[$symptom];
                
                for ($i = 0; $i < count($data['diseases']); $i++) {
                    $disease = $data['diseases'][$i];
                    $weight = $data['weights'][$i];
                    
                    if (!isset($diseaseScores[$disease])) {
                        $diseaseScores[$disease] = 0;
                    }
                    $diseaseScores[$disease] += $weight;
                }

                // Check for critical symptoms
                if (in_array($symptom, ['chest_pain', 'shortness_of_breath', 'severe_headache', 'abdominal_pain'])) {
                    $criticalSymptomsDetected[] = $symptom;
                }
            }
        }

        // Sort diseases by score
        arsort($diseaseScores);

        // Analyze specific disease patterns
        $patternMatches = [];
        foreach ($this->diseasePatterns as $disease => $pattern) {
            $matchedSymptoms = array_intersect($symptoms, $pattern['symptoms']);
            $matchCount = count($matchedSymptoms);
            
            if ($matchCount >= $pattern['min_symptoms']) {
                $confidence = $matchCount / count($pattern['symptoms']);
                $patternMatches[$disease] = [
                    'confidence' => $confidence,
                    'matched_symptoms' => $matchedSymptoms,
                    'critical_match' => !empty(array_intersect($symptoms, $pattern['critical_symptoms']))
                ];
            }
        }

        // Combine results
        $allDiseases = array_keys(array_merge($diseaseScores, $patternMatches));
        $finalScores = [];

        foreach ($allDiseases as $disease) {
            $score = $diseaseScores[$disease] ?? 0;
            if (isset($patternMatches[$disease])) {
                $score = max($score, $patternMatches[$disease]['confidence'] * 100);
            }
            $finalScores[$disease] = min($score, 100); // Cap at 100
        }

        // Sort by final score
        arsort($finalScores);

        // Determine urgency level
        if (!empty($criticalSymptomsDetected)) {
            $results['urgency_level'] = 'critical';
        } elseif (array_sum($finalScores) > 150) {
            $results['urgency_level'] = 'high';
        } elseif (array_sum($finalScores) > 80) {
            $results['urgency_level'] = 'medium';
        }

        // Get top 5 possible diseases
        $results['possible_diseases'] = array_slice(array_keys($finalScores), 0, 5, true);
        $results['confidence_scores'] = array_slice($finalScores, 0, 5, true);
        $results['critical_symptoms'] = $criticalSymptomsDetected;

        // Determine recommended specialist
        if (!empty($results['possible_diseases'])) {
            $topDisease = $results['possible_diseases'][0];
            if (isset($this->diseasePatterns[$topDisease])) {
                $results['recommended_specialist'] = $this->diseasePatterns[$topDisease]['specialist'];
            }
        }

        // Generate recommendations
        $results['recommendations'] = $this->generateRecommendations($results, $age, $gender);

        return $results;
    }

    private function generateRecommendations($results, $age, $gender) {
        $recommendations = [];

        // Urgency-based recommendations
        switch ($results['urgency_level']) {
            case 'critical':
                $recommendations[] = "🚨 Seek immediate medical attention - Emergency room visit recommended";
                $recommendations[] = "Call emergency services if symptoms worsen";
                break;
            case 'high':
                $recommendations[] = "⚠️ Consult a doctor within 24 hours";
                $recommendations[] = "Monitor symptoms closely";
                break;
            case 'medium':
                $recommendations[] = "📅 Schedule a doctor appointment this week";
                $recommendations[] = "Rest and monitor symptoms";
                break;
            default:
                $recommendations[] = "🏥 Consider consulting a general physician";
                $recommendations[] = "Monitor symptoms and seek care if they worsen";
        }

        // Age-specific recommendations
        if ($age) {
            if ($age > 65) {
                $recommendations[] = "👴 Elderly patients should seek medical attention earlier";
            } elseif ($age < 12) {
                $recommendations[] = "👶 Pediatric consultation recommended for children";
            }
        }

        // Symptom-specific recommendations
        if (in_array('fever', array_keys($this->symptomsDatabase))) {
            $recommendations[] = "🌡️ Monitor temperature regularly";
        }
        if (in_array('cough', array_keys($this->symptomsDatabase))) {
            $recommendations[] = "💧 Stay hydrated and avoid irritants";
        }

        return $recommendations;
    }

    public function getDiseaseDetails($diseaseName) {
        if (isset($this->diseasePatterns[$diseaseName])) {
            return $this->diseasePatterns[$diseaseName];
        }
        return null;
    }

    public function getSymptomList() {
        return array_keys($this->symptomsDatabase);
    }

    public function validateSymptoms($symptoms) {
        $validSymptoms = [];
        $invalidSymptoms = [];

        foreach ($symptoms as $symptom) {
            $symptom = strtolower(trim($symptom));
            if (isset($this->symptomsDatabase[$symptom])) {
                $validSymptoms[] = $symptom;
            } else {
                $invalidSymptoms[] = $symptom;
            }
        }

        return [
            'valid' => $validSymptoms,
            'invalid' => $invalidSymptoms
        ];
    }
}

// Example usage and API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'analyze_symptoms') {
    include "../db.php";
    
    $symptoms = isset($_POST['symptoms']) ? explode(',', $_POST['symptoms']) : [];
    $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    
    $engine = new DiseaseEngine();
    $results = $engine->analyzeSymptoms($symptoms, $age, $gender);
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Helper function to format symptoms for display
function formatSymptoms($symptoms) {
    return array_map(function($symptom) {
        return ucwords(str_replace('_', ' ', $symptom));
    }, $symptoms);
}

// Helper function to get urgency color
function getUrgencyColor($urgency) {
    switch ($urgency) {
        case 'critical': return '#dc3545';
        case 'high': return '#fd7e14';
        case 'medium': return '#ffc107';
        default: return '#28a745';
    }
}

// Legacy function for backward compatibility
function diseaseCategory($disease) {
    $map = [
        "Diabetes" => "Endocrinology",
        "Cancer" => "Oncology",
        "Heart Attack" => "Cardiology",
        "Asthma" => "Pulmonology"
    ];
    return $map[$disease] ?? "General Medicine";
}
?>
