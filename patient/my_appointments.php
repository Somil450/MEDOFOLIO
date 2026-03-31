<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include "../db.php";

$patient_id = (int) $_SESSION['user_id'];

/* ================= FETCH APPOINTMENTS ================= */
$q = mysqli_query($conn, "
    SELECT * FROM appointments
    WHERE patient_id = $patient_id
    ORDER BY appointment_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Appointments</title>
    <link rel="stylesheet" href="../assets/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
    <h2>My Appointments</h2>
    <a href="patient_profile.php">Back</a>
</div>

<?php if (mysqli_num_rows($q) > 0): ?>
<?php while ($a = mysqli_fetch_assoc($q)): ?>

<div class="card event active">

    <p><b>Disease:</b> <?= htmlspecialchars($a['disease']) ?></p>
    <p><b>Specialist:</b> <?= htmlspecialchars($a['specialty']) ?></p>
    <p><b>Hospital:</b> <?= htmlspecialchars($a['hospital_type']) ?></p>

    <p><b>Date:</b> <?= htmlspecialchars($a['appointment_date']) ?></p>
    <p><b>Time:</b> <?= htmlspecialchars($a['appointment_time']) ?></p>
    <p><b>Status:</b> <?= htmlspecialchars($a['status']) ?></p>

    <!-- ACTION BUTTONS -->
    <div style="margin-top:15px; display:flex; gap:12px; flex-wrap:wrap;">

        <!-- ================= RESCHEDULE ================= -->
        <form method="POST"
              action="reschedule_appointment.php"
              class="reschedule-form"
              style="flex:1;">

            <input type="hidden" name="appointment_id"
                   value="<?= $a['appointment_id'] ?>">

            <!-- STEP 1 BUTTON -->
            <button type="button"
                    class="btn secondary reschedule-btn"
                    onclick="showReschedule(this)">
                🔄 Reschedule
            </button>

            <!-- STEP 2 FIELDS -->
            <div class="reschedule-fields"
                 style="display:none; margin-top:15px;">

                <label>New Date</label>
                <input type="date" name="new_date" required>

                <label>New Time</label>
                <input type="time" name="new_time" required>

                <div style="margin-top:15px; display:flex; gap:12px; flex-wrap:wrap;">
                    <button type="submit" class="btn secondary">
                        ✅ Confirm
                    </button>

                    <button type="button"
                            class="btn danger"
                            onclick="cancelReschedule(this)">
                        ❌ Cancel
                    </button>
                </div>
            </div>
        </form>

        <!-- ================= CANCEL APPOINTMENT ================= -->
        <form method="POST"
              action="cancel_appointment.php"
              class="cancel-appointment-form"
              onsubmit="return confirm('Cancel this appointment?');">

            <input type="hidden" name="appointment_id"
                   value="<?= $a['appointment_id'] ?>">

            <button type="submit" class="btn danger">
                ❌ Cancel Appointment
            </button>
        </form>

    </div>
</div>

<?php endwhile; ?>
<?php else: ?>
<div class="card">
    <p>No appointments booked yet.</p>
</div>
<?php endif; ?>

<script>
function showReschedule(btn) {
    const card = btn.closest(".card");
    const resForm = btn.closest(".reschedule-form");
    const fields = resForm.querySelector(".reschedule-fields");
    const cancelForm = card.querySelector(".cancel-appointment-form");

    btn.style.display = "none";
    fields.style.display = "block";
    cancelForm.style.display = "none";
}

function cancelReschedule(btn) {
    const resForm = btn.closest(".reschedule-form");
    const card = btn.closest(".card");

    resForm.querySelector(".reschedule-fields").style.display = "none";
    resForm.querySelector(".reschedule-btn").style.display = "inline-block";
    card.querySelector(".cancel-appointment-form").style.display = "inline-block";
}
</script>

</body>
</html>
