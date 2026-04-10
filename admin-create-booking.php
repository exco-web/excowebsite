<?php
session_start();
include("connections.php");
include("functions.php");
$user_data = check_login($con);

if ($user_data['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index");
    exit();
}

// Ensure guest columns exist (run once, safe to re-run)
mysqli_query($con, "ALTER TABLE bookings MODIFY COLUMN user_id BIGINT NULL");
mysqli_query($con, "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_name VARCHAR(255) NULL");
mysqli_query($con, "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_email VARCHAR(255) NULL");
mysqli_query($con, "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_phone VARCHAR(50) NULL");

$allowed_times  = ['10:00:00','11:00:00','12:00:00','13:00:00','14:00:00','15:00:00','16:00:00','17:00:00','18:00:00'];
$time_labels    = ['10:00:00'=>'10am','11:00:00'=>'11am','12:00:00'=>'12pm','13:00:00'=>'1pm','14:00:00'=>'2pm','15:00:00'=>'3pm','16:00:00'=>'4pm','17:00:00'=>'5pm','18:00:00'=>'6pm'];
$allowed_reasons = ['Initial Consultation', 'Follow-up Appointment', 'Document Review', 'Legal Advice', 'Financial Planning', 'Other'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $guest_name  = trim($_POST['guest_name']  ?? '');
    $guest_email = trim($_POST['guest_email'] ?? '');
    $guest_phone = trim($_POST['guest_phone'] ?? '');
    $notes       = trim($_POST['notes']       ?? '');
    $new_date    = isset($_POST['date'])   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'])   ? $_POST['date']   : '';
    $new_time   = isset($_POST['time'])   && in_array($_POST['time'],   $allowed_times)   ? $_POST['time']   : '';
    $new_reason = isset($_POST['reason']) && in_array($_POST['reason'], $allowed_reasons) ? $_POST['reason'] : null;

    if (!$guest_name) $errors[] = "Full name is required.";
    if (!$new_date)   $errors[] = "Please enter a valid date.";
    if (!$new_time)   $errors[] = "Please select a time slot.";

    if (!$errors) {
        $stmt = mysqli_prepare($con, "INSERT INTO bookings (user_id, date, time, status, reason, guest_name, guest_email, guest_phone, notes) VALUES (NULL, ?, ?, 'confirmed', ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssssss", $new_date, $new_time, $new_reason, $guest_name, $guest_email, $guest_phone, $notes);
        mysqli_stmt_execute($stmt);
        log_booking($con, mysqli_insert_id($con), 'created', 'admin', "Guest: {$guest_name}, Date: {$new_date}, Time: {$new_time}");
        header("Location: " . BASE_URL . "/admin");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php $page_title = 'Create Booking'; include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-loggedin.php'; ?>

        <div class="admin__page" style="text-align:center;">
            <h1 class="booking__heading">Create Booking</h1>

            <?php if ($errors): ?>
                <div class="admin__errors">
                    <?php foreach ($errors as $e): ?>
                        <p><?= htmlspecialchars($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="admin__create-form" style="text-align:left;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <div class="admin__create-field">
                    <label for="guest_name">Full Name <span style="color:var(--accent)">*</span></label>
                    <input type="text" name="guest_name" id="guest_name" class="admin__select admin__select--wide"
                           placeholder="e.g. Jane Smith"
                           value="<?= htmlspecialchars($_POST['guest_name'] ?? '') ?>">
                </div>

                <div class="admin__create-field">
                    <label for="guest_email">Email</label>
                    <input type="text" name="guest_email" id="guest_email" class="admin__select admin__select--wide"
                           placeholder="e.g. jane@example.com"
                           value="<?= htmlspecialchars($_POST['guest_email'] ?? '') ?>">
                </div>

                <div class="admin__create-field">
                    <label for="guest_phone">Phone Number</label>
                    <input type="text" name="guest_phone" id="guest_phone" class="admin__select admin__select--wide"
                           placeholder="e.g. 07700 900000"
                           value="<?= htmlspecialchars($_POST['guest_phone'] ?? '') ?>">
                </div>

                <div class="admin__create-row">
                    <div class="admin__create-field">
                        <label for="date">Date <span style="color:var(--accent)">*</span></label>
                        <input type="date" name="date" id="date" class="admin__select"
                               value="<?= htmlspecialchars($_POST['date'] ?? '') ?>">
                    </div>
                    <div class="admin__create-field">
                        <label for="time">Time <span style="color:var(--accent)">*</span></label>
                        <select name="time" id="time" class="admin__select">
                            <?php foreach ($time_labels as $val => $label): ?>
                                <option value="<?= $val ?>"
                                    <?= (isset($_POST['time']) && $_POST['time'] === $val) ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="admin__create-field">
                    <label for="reason">Reason</label>
                    <select name="reason" id="reason" class="admin__select admin__select--wide">
                        <option value="">— None —</option>
                        <?php foreach ($allowed_reasons as $r): ?>
                            <option value="<?= $r ?>" <?= (isset($_POST['reason']) && $_POST['reason'] === $r) ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin__create-field">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" class="notes-modal__textarea" placeholder="Optional" rows="5"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>

                <div class="admin__create-actions">
                    <button type="submit" class="admin__save">Create Booking</button>
                    <a href="<?= BASE_URL ?>/admin" class="admin__clear">Cancel</a>
                </div>
            </form>
        </div>

        <script src="<?= BASE_URL ?>/app.js"></script>
    </body>
</html>
