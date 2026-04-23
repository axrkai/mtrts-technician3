<?php
// modules/workorders/save.php — POST handler for create/edit work orders.
// No HTML output. Validates, saves, redirects.

$module = 'workorders';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$wo_id   = (int)($_POST['wo_id'] ?? 0);
$is_edit = $wo_id > 0;
$data    = sanitize_wo_post($_POST, $user_id);
$errors  = [];

// ── Validation ────────────────────────────────────────────────

if (empty($data['wo_type'])) {
    $errors['wo_type'] = 'Work order type is required.';
}

if (empty($data['assigned_role_id'])) {
    $errors['assigned_role_id'] = 'Queue (role) is required.';
}

// Scheduled end must be after start
if ($data['scheduled_start'] && $data['scheduled_end']) {
    if (strtotime($data['scheduled_end']) <= strtotime($data['scheduled_start'])) {
        $errors['scheduled_end'] = 'Scheduled end must be after the start.';
    }
}

// On hold requires a reason
if ($is_edit && $data['status'] === 'on_hold' && empty($data['on_hold_reason'])) {
    $errors['on_hold_reason'] = 'Please select a reason for putting this on hold.';
}

// ── If errors, redirect back ──────────────────────────────────

if ($errors) {
    $_SESSION['wo_errors'] = $errors;
    $_SESSION['wo_old']    = $_POST;
    $back = $is_edit ? 'edit.php?id=' . $wo_id : 'add.php';
    header('Location: ' . $back);
    exit;
}

// ── Save ──────────────────────────────────────────────────────

if ($is_edit) {
    // Track assignment change for notification
    $old_wo = get_wo_by_id($pdo, $wo_id);
    $old_assignee = $old_wo['assigned_to'] ?? null;

    update_work_order($pdo, $wo_id, $data);

    // Normalize status based on assignment (Technician Ops only lists assigned/scheduled/in_progress)
    // If a WO is assigned but still marked as 'new', push it into the technician queue.
    if (!empty($data['assigned_to']) && ($data['status'] ?? '') === 'new') {
        $pdo->prepare("UPDATE work_orders SET status = 'assigned' WHERE wo_id = ?")->execute([$wo_id]);
    }
    // If a WO becomes unassigned, prevent it from staying in technician-only statuses.
    if (empty($data['assigned_to']) && in_array(($data['status'] ?? ''), ['assigned', 'scheduled', 'in_progress'], true)) {
        $pdo->prepare("UPDATE work_orders SET status = 'new' WHERE wo_id = ?")->execute([$wo_id]);
    }

    // If assignment changed, log it and notify
    if ($data['assigned_to'] && $data['assigned_to'] != $old_assignee) {
        $pdo->prepare("
            INSERT INTO wo_assignment_log (wo_id, assigned_from, assigned_to, assigned_by, reason)
            VALUES (?,?,?,?,?)
        ")->execute([
            $wo_id,
            $old_assignee ?: null,
            $data['assigned_to'],
            $user_id,
            'Updated via edit form',
        ]);

        // Notify new technician
        $wo_num = $old_wo['wo_number'] ?? '';
        require_once __DIR__ . '/../notifications/functions.php';
        notify_user(
            $pdo,
            (int)$data['assigned_to'],
            'Work Order Assigned: ' . $wo_num,
            'You have been assigned to work order ' . $wo_num . '.',
            BASE_URL . 'modules/workorders/view.php?id=' . $wo_id
        );
    }
} else {
    $wo_id = create_work_order($pdo, $data);

    // Notify assigned technician
    if ($data['assigned_to']) {
        $wo_row = get_wo_by_id($pdo, $wo_id);
        require_once __DIR__ . '/../notifications/functions.php';
        notify_user(
            $pdo,
            (int)$data['assigned_to'],
            'New Work Order: ' . ($wo_row['wo_number'] ?? ''),
            'You have been assigned a new work order.',
            BASE_URL . 'modules/workorders/view.php?id=' . $wo_id
        );
    }
}

header('Location: ' . BASE_URL . 'modules/workorders/view.php?id=' . $wo_id);
exit;
