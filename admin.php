<?php
require 'vendor/autoload.php';

session_start();
include("connections.php");
include("functions.php");
$user_data = check_login($con);

if ($user_data['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

mysqli_query($con, "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS notes TEXT NULL");
mysqli_query($con, "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS reason VARCHAR(100) NULL");

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['action']) && $_POST['action'] === 'remove') {
    $stmt = mysqli_prepare($con, "DELETE FROM bookings WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_POST['booking_id']);
    mysqli_stmt_execute($stmt);
    header("Location: " . BASE_URL . "/admin.php?" . http_build_query($_GET));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $allowed_statuses   = ['pending', 'confirmed', 'cancelled'];
    $allowed_times_post = ['10:00:00','11:00:00','12:00:00','13:00:00','14:00:00','15:00:00','16:00:00','17:00:00','18:00:00'];
    $allowed_reasons    = ['Initial Consultation', 'Follow-up Appointment', 'Document Review', 'Legal Advice', 'Financial Planning', 'Other'];

    if (in_array($_POST['status'], $allowed_statuses)) {
        $booking_id = (int)$_POST['booking_id'];
        $new_status = $_POST['status'];
        $new_date   = isset($_POST['new_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['new_date']) ? $_POST['new_date'] : null;
        $new_time   = isset($_POST['new_time']) && in_array($_POST['new_time'], $allowed_times_post) ? $_POST['new_time'] : null;
        $new_notes  = isset($_POST['notes']) ? trim($_POST['notes']) : null;
        $new_reason = isset($_POST['reason']) && in_array($_POST['reason'], $allowed_reasons) ? $_POST['reason'] : null;

        // Fetch current booking to detect changes and get client contact
        $prev_stmt = mysqli_prepare($con, "
            SELECT b.status, b.date, b.time, b.reason,
                   COALESCE(u.name,  b.guest_name)  AS name,
                   COALESCE(u.email, b.guest_email) AS email
            FROM bookings b
            LEFT JOIN users u ON u.user_id = b.user_id
            WHERE b.id = ?
        ");
        mysqli_stmt_bind_param($prev_stmt, "i", $booking_id);
        mysqli_stmt_execute($prev_stmt);
        $prev = mysqli_fetch_assoc(mysqli_stmt_get_result($prev_stmt));

        if ($new_date && $new_time) {
            $stmt = mysqli_prepare($con, "UPDATE bookings SET status = ?, date = ?, time = ?, notes = ?, reason = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssssi", $new_status, $new_date, $new_time, $new_notes, $new_reason, $booking_id);
        } else {
            $new_date = $prev['date'];
            $new_time = $prev['time'];
            $stmt = mysqli_prepare($con, "UPDATE bookings SET status = ?, notes = ?, reason = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $new_status, $new_notes, $new_reason, $booking_id);
        }
        mysqli_stmt_execute($stmt);

        // Email client if status changed or date/time changed
        $status_changed   = $prev && $prev['status'] !== $new_status;
        $datetime_changed = $prev && ($prev['date'] !== $new_date || $prev['time'] !== $new_time);

        if ($prev && $prev['email'] && ($status_changed || $datetime_changed)) {
            $client_name = htmlspecialchars($prev['name'] ?? 'there');
            $date_fmt    = date('l, F j Y', strtotime($new_date));
            $time_fmt    = date('H:i', strtotime($new_time));
            $reason_fmt  = htmlspecialchars($new_reason ?? $prev['reason'] ?? 'your appointment');

            $status_labels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'];
            $status_fmt    = $status_labels[$new_status] ?? ucfirst($new_status);

            $changes = [];
            if ($status_changed)   $changes[] = "<strong>Status:</strong> {$status_fmt}";
            if ($datetime_changed) $changes[] = "<strong>Date:</strong> {$date_fmt}<br><strong>Time:</strong> {$time_fmt}";
            $changes_html = implode('<br>', $changes);

            try {
                $resend = Resend::client(RESEND_API_KEY);
                $resend->emails->send([
                    'from'    => MAIL_FROM,
                    'to'      => $prev['email'],
                    'subject' => 'Your booking has been updated — Expert Consult',
                    'html'    => "
                        <p>Hi {$client_name},</p>
                        <p>Your booking with <strong>Expert Consult</strong> has been updated:</p>
                        <p>{$changes_html}<br><strong>Reason:</strong> {$reason_fmt}</p>
                        <p>If you have any questions, please contact us.</p>
                        <p>Expert Consult</p>
                    ",
                ]);
            } catch (Exception $e) {
                // Silent fail
            }
        }
    }
    header("Location: " . BASE_URL . "/admin.php?" . http_build_query($_GET));
    exit();
}

$allowed_times = ['10:00:00','11:00:00','12:00:00','13:00:00','14:00:00','15:00:00','16:00:00','17:00:00','18:00:00'];
$time_labels   = ['10:00:00'=>'10am','11:00:00'=>'11am','12:00:00'=>'12pm','13:00:00'=>'1pm','14:00:00'=>'2pm','15:00:00'=>'3pm','16:00:00'=>'4pm','17:00:00'=>'5pm','18:00:00'=>'6pm'];

// Filters from GET
$allowed_statuses = ['pending', 'confirmed', 'cancelled'];
$filter_status  = isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses) ? $_GET['status'] : '';
$filter_date    = isset($_GET['date'])   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : '';
$filter_time    = isset($_GET['time'])   && in_array($_GET['time'], $allowed_times) ? $_GET['time'] : '';
$filter_from    = isset($_GET['from'])   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from']) ? $_GET['from'] : '';
$filter_to      = isset($_GET['to'])     && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'])   ? $_GET['to']   : '';
$filter_search  = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch distinct dates for dropdown
$dates_result = mysqli_query($con, "SELECT DISTINCT date FROM bookings ORDER BY date ASC");
$available_dates = [];
while ($r = mysqli_fetch_assoc($dates_result)) {
    $available_dates[] = $r['date'];
}

$conditions = [];
$params     = [];
$types      = '';

if ($filter_status) {
    $conditions[] = "b.status = ?";
    $params[] = $filter_status;
    $types   .= 's';
}
if ($filter_date) {
    $conditions[] = "b.date = ?";
    $params[] = $filter_date;
    $types   .= 's';
}
if ($filter_time) {
    $conditions[] = "b.time = ?";
    $params[] = $filter_time;
    $types   .= 's';
}
if ($filter_from) {
    $conditions[] = "b.date >= ?";
    $params[] = $filter_from;
    $types   .= 's';
}
if ($filter_to) {
    $conditions[] = "b.date <= ?";
    $params[] = $filter_to;
    $types   .= 's';
}
if ($filter_search) {
    $conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
    $types   .= 'ss';
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "
    SELECT b.id, b.date, b.time, b.status, b.created_at, b.notes, b.reason,
           COALESCE(u.name,   b.guest_name)  AS name,
           COALESCE(u.email,  b.guest_email) AS email,
           COALESCE(u.number, b.guest_phone) AS number
    FROM bookings b
    LEFT JOIN users u ON u.user_id = b.user_id
    $where
    ORDER BY b.date ASC
";

if ($params) {
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $bookings = mysqli_stmt_get_result($stmt);
} else {
    $bookings = mysqli_query($con, $sql);
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/head.php'; ?>
    <body>
        <?php include 'includes/navbar-loggedin.php'; ?>

        <div class="admin__page">
            <h1 class="booking__heading">Admin — All Bookings</h1>

            <form method="GET" class="admin__filters">
                <input type="text" name="search" placeholder="Name or email" value="<?= htmlspecialchars($filter_search) ?>">
                <select name="status" class="admin__select">
                    <option value="">All statuses</option>
                    <option value="pending"   <?= $filter_status === 'pending'   ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $filter_status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <input type="date" name="from" value="<?= htmlspecialchars($filter_from) ?>">
                <span style="color:#666;">to</span>
                <input type="date" name="to" value="<?= htmlspecialchars($filter_to) ?>">
                <button type="submit" class="admin__save">Filter</button>
                <?php if ($filter_status || $filter_from || $filter_to || $filter_search): ?>
                    <a href="<?= BASE_URL ?>/admin.php" class="admin__clear">Clear</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/admin-create-booking.php" class="admin__save" style="text-decoration:none; margin-left:auto;">+ Create Booking</a>
            </form>

            <?php include 'includes/bookings-chart.php'; ?>

            <div class="table__wrapper">
            <table class="content__table content__table--bookings">
                <tr>
                    <td><strong>Name</strong></td>
                    <td><strong>Email</strong></td>
                    <td><strong>Phone</strong></td>
                    <td><strong>Date/Time</strong></td>
                    <td><strong>Status</strong></td>
                    <td><strong>Reason</strong></td>
                    <td><strong>Notes</strong></td>
                    <td><strong>Booked On</strong></td>
                    <td></td>
                </tr>
                <?php
                $rows = $bookings ? mysqli_fetch_all($bookings, MYSQLI_ASSOC) : [];
                ?>
                <?php if ($rows): ?>
                    <?php foreach ($rows as $i => $b):
                        $next = $rows[$i + 1] ?? null;
                        $sameAsNext = $next && $next['date'] === $b['date'] && $next['time'] === $b['time'];
                    ?>
                        <tr <?= $sameAsNext ? 'class="no-border"' : '' ?>>
                            <td><?= htmlspecialchars($b['name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($b['email'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($b['number'] ?? '—') ?></td>
                            <td>
                                <div style="display:inline-flex; gap:0.5rem; align-items:center;">
                                    <input type="date" name="new_date" form="edit-<?= (int)$b['id'] ?>" value="<?= htmlspecialchars($b['date']) ?>" class="admin__select">
                                    <select name="new_time" form="edit-<?= (int)$b['id'] ?>" class="admin__select">
                                        <?php foreach ($time_labels as $val => $label): ?>
                                            <option value="<?= $val ?>" <?= $b['time'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                            <td><span class="bookings__status bookings__status--<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars(ucfirst($b['status'])) ?></span></td>
                            <td>
                                <select name="reason" form="edit-<?= (int)$b['id'] ?>" class="admin__select">
                                    <option value="">— None —</option>
                                    <?php foreach (['Initial Consultation','Follow-up Appointment','Document Review','Legal Advice','Financial Planning','Other'] as $r): ?>
                                        <option value="<?= $r ?>" <?= ($b['reason'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="admin__notes-btn"
                                    data-id="<?= (int)$b['id'] ?>"
                                    data-notes="<?= htmlspecialchars($b['notes'] ?? '') ?>">
                                    <?= !empty($b['notes']) ? '<span class="admin__notes-preview">' . htmlspecialchars(mb_strimwidth($b['notes'], 0, 30, '…')) . '</span>' : '<span class="admin__notes-empty">+ Add note</span>' ?>
                                    <i class="fas fa-pencil-alt admin__notes-icon"></i>
                                </button>
                                <input type="hidden" name="notes" form="edit-<?= (int)$b['id'] ?>" value="<?= htmlspecialchars($b['notes'] ?? '') ?>" class="admin__notes-hidden" id="notes-hidden-<?= (int)$b['id'] ?>">
                            </td>
                            <td><?= date('j M Y', strtotime($b['created_at'])) ?></td>
                            <td style="text-align:right;">
                                <form method="POST" id="edit-<?= (int)$b['id'] ?>" style="display:inline-flex; gap:0.5rem; align-items:center;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                    <select name="status" class="admin__select">
                                        <option value="pending"   <?= $b['status'] === 'pending'   ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="cancelled" <?= $b['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="admin__save">Save</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="admin__remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="color:#666;">No bookings found.</td>
                    </tr>
                <?php endif; ?>
            </table>
            </div>
        </div>

        <div class="notes-modal__overlay" id="notes-modal" style="display:none;">
            <div class="notes-modal__box">
                <div class="notes-modal__header">
                    <h3 class="notes-modal__title">Booking Notes</h3>
                    <button type="button" class="notes-modal__close" id="notes-modal-close">&times;</button>
                </div>
                <textarea class="notes-modal__textarea" id="notes-modal-textarea" placeholder="Write notes here…" rows="10"></textarea>
                <div class="notes-modal__actions">
                    <button type="button" class="admin__save" id="notes-modal-save">Save</button>
                    <button type="button" class="admin__remove" id="notes-modal-cancel">Cancel</button>
                </div>
            </div>
        </div>

        <script src="<?= BASE_URL ?>/app.js"></script>
        <script>
            const modal       = document.getElementById('notes-modal');
            const textarea    = document.getElementById('notes-modal-textarea');
            const closeBtn    = document.getElementById('notes-modal-close');
            const cancelBtn   = document.getElementById('notes-modal-cancel');
            const saveBtn     = document.getElementById('notes-modal-save');
            let activeId      = null;

            document.querySelectorAll('.admin__notes-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    activeId = btn.dataset.id;
                    textarea.value = btn.dataset.notes;
                    modal.style.display = 'flex';
                    textarea.focus();
                });
            });

            function closeModal() {
                modal.style.display = 'none';
                activeId = null;
            }

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

            saveBtn.addEventListener('click', () => {
                if (!activeId) return;
                const notes = textarea.value;
                const hidden = document.getElementById('notes-hidden-' + activeId);
                const btn = document.querySelector(`.admin__notes-btn[data-id="${activeId}"]`);

                hidden.value = notes;
                hidden.dispatchEvent(new Event('input'));
                btn.dataset.notes = notes;

                if (notes.trim()) {
                    btn.innerHTML = `<span class="admin__notes-preview">${notes.substring(0, 30)}${notes.length > 30 ? '…' : ''}</span>`;
                } else {
                    btn.innerHTML = `<span class="admin__notes-empty">+ Add note</span>`;
                }

                document.getElementById('edit-' + activeId).requestSubmit();
                closeModal();
            });

            // Grey out Save buttons until a change is made
            document.querySelectorAll('form[id^="edit-"]').forEach(form => {
                const formId  = form.id;
                const saveBtn = form.querySelector('.admin__save');
                saveBtn.disabled = true;

                // Collect all fields: inside the form + external fields referencing it via form="id"
                function getFields() {
                    return [
                        ...form.querySelectorAll('input, select, textarea'),
                        ...document.querySelectorAll(`[form="${formId}"]`)
                    ];
                }

                const snapshot = {};
                getFields().forEach(el => {
                    if (el.name) snapshot[el.name] = el.value;
                });

                function checkChanged() {
                    const changed = getFields().some(el => el.name && el.value !== snapshot[el.name]);
                    saveBtn.disabled = !changed;
                    if (!saveBtn.disabled) saveBtn.style.borderColor = saveBtn.style.color = '#4caf50';
                    else saveBtn.style.borderColor = saveBtn.style.color = '';
                }

                getFields().forEach(el => {
                    el.addEventListener('change', checkChanged);
                    el.addEventListener('input', checkChanged);
                });

            });
        </script>
    </body>
</html>
