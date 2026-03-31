<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include "../db.php";

$patient_id = (int) $_SESSION['user_id'];

/* HANDLE FORM SUBMIT */
if (isset($_POST['save'])) {

    $disease_id = (int) $_POST['disease'];
    $date       = mysqli_real_escape_string($conn, $_POST['date']);
    $severity   = mysqli_real_escape_string($conn, $_POST['severity']);
    $status     = mysqli_real_escape_string($conn, $_POST['status']);
    $hospital   = mysqli_real_escape_string($conn, $_POST['hospital']);
    $notes      = mysqli_real_escape_string($conn, $_POST['notes']);

    $sql = "
        INSERT INTO patient_disease_history
        (patient_id, disease_id, detected_date, severity_level, status, treating_hospital, notes)
        VALUES
        ('$patient_id', '$disease_id', '$date', '$severity', '$status', '$hospital', '$notes')
    ";

    if (mysqli_query($conn, $sql)) {
        header("Location: patient_profile.php");
        exit;
    } else {
        $error = "❌ Error saving disease history.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Disease</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="card" style="max-width:600px;margin:50px auto;">
    <h2>➕ Add Disease</h2>

    <?php if (isset($error)): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">

        <!-- Disease -->
        <label>Disease</label>
        <select name="disease" required>
            <option value="">Select Disease</option>
            <?php
            $d = mysqli_query($conn, "SELECT * FROM disease_master ORDER BY disease_name");
            while ($r = mysqli_fetch_assoc($d)) {
                echo "<option value='{$r['disease_id']}'>
                        {$r['disease_name']}
                      </option>";
            }
            ?>
        </select>

        <!-- Date -->
        <label>Detected Date</label>
        <input type="date" name="date" required>

        <!-- Severity -->
        <label>Severity Level</label>
        <select name="severity" required>
            <option value="">Select Severity</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
        </select>

        <!-- Status -->
        <label>Status</label>
        <select name="status" required>
            <option value="">Select Status</option>
            <option value="Active">Active</option>
            <option value="Critical">Critical</option>
            <option value="Recovered">Recovered</option>
        </select>

        <!-- Hospital -->
        <label>Treating Hospital</label>
        <input type="text" name="hospital"
               placeholder="Hospital / Clinic Name" required>

        <!-- Notes -->
        <label>Notes (optional)</label>
        <textarea name="notes"
                  placeholder="Additional medical notes..."></textarea>

        <button class="btn" type="submit" name="save">
            💾 Save Disease
        </button>
    </form>
</div>

</body>
</html>
