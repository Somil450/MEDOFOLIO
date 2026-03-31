<?php
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
    $summary .= "• Relevant specialists based on the condition";

    return $summary;
}
