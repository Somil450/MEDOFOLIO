<?php

function getDoctors($conn, $specialty, $region)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT doctor_name, specialty, experience, rating 
         FROM doctors 
         WHERE specialty = ? AND region = ?"
    );

    mysqli_stmt_bind_param($stmt, "ss", $specialty, $region);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $doctors = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $doctors[] = $row;
    }

    return $doctors;
}
