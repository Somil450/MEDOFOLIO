<?php

function getPrescription($disease) {

    $d = strtolower($disease);

    if (strpos($d, 'asthma') !== false) {
        return [
            "medicines" => [
                "Salbutamol Inhaler – 2 puffs when needed",
                "Budesonide Inhaler – twice daily"
            ],
            "advice" => [
                "Avoid dust and smoke",
                "Use inhaler correctly",
                "Regular breathing exercises"
            ],
            "followup" => "Pulmonologist review in 2 weeks"
        ];
    }

    if (strpos($d, 'hypertension') !== false) {
        return [
            "medicines" => [
                "Amlodipine 5mg – once daily",
                "Losartan 50mg – once daily"
            ],
            "advice" => [
                "Low salt diet",
                "Daily walking",
                "Stress management"
            ],
            "followup" => "BP check every 15 days"
        ];
    }

    if (strpos($d, 'heart') !== false) {
        return [
            "medicines" => [
                "Aspirin 75mg – once daily",
                "Atorvastatin 20mg – at night"
            ],
            "advice" => [
                "Avoid oily food",
                "Light exercise only",
                "Monitor chest pain"
            ],
            "followup" => "Cardiologist follow-up in 1 month"
        ];
    }

    if (strpos($d, 'cancer') !== false) {
        return [
            "medicines" => [
                "As advised by oncologist",
                "Supportive pain management drugs"
            ],
            "advice" => [
                "Regular hospital visits",
                "Proper nutrition",
                "Mental health support"
            ],
            "followup" => "Strict oncologist supervision"
        ];
    }

    return [
        "medicines" => ["General multivitamins"],
        "advice" => ["Healthy lifestyle"],
        "followup" => "Doctor consultation if symptoms persist"
    ];
}
