<?php
/**
 * Hospital Recommendation Engine
 * --------------------------------
 * This file ONLY contains functions.
 * No session_start(), no HTML.
 */

/**
 * Get hospitals based on disease + region
 */
function getHospitals($conn, $diseaseName, $region) {

    // Map disease to specialty
    $specialtyMap = [
        "Diabetes"     => "Diabetes",
        "Cancer"       => "Oncology",
        "Heart Attack" => "Cardiology",
        "Asthma"       => "Pulmonology"
    ];

    $specialty = $specialtyMap[$diseaseName] ?? "";

    // Build query
    $sql = "SELECT hospital_name, region, specialty, rating, latitude, longitude
            FROM hospitals
            WHERE region = ?";

    if ($specialty !== "") {
        $sql .= " AND specialty LIKE ?";
    }

    $sql .= " ORDER BY rating DESC LIMIT 5";

    $stmt = mysqli_prepare($conn, $sql);

    if ($specialty !== "") {
        $like = "%$specialty%";
        mysqli_stmt_bind_param($stmt, "ss", $region, $like);
    } else {
        mysqli_stmt_bind_param($stmt, "s", $region);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $hospitals = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $hospitals[] = $row;
    }

    return $hospitals;
}
