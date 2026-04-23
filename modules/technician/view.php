<?php
$module = 'technician';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';

$wo_id = (int)($_GET['id'] ?? 0);
if (!$wo_id) {
    header('Location: ' . BASE_URL . 'modules/technician/index.php');
    exit;
}

$wo = get_work_order_detail($pdo, $wo_id);
if (!$wo) {
    http_response_code(404);
    echo 'Work order not found';
    exit;
}

// Everyone can view all work orders. Execution is role-gated (queue-without-claim).
$role_id = (int)($_SESSION['role_id'] ?? 0);
$is_admin = tech_is_admin_role($pdo);
$assigned_role_id = technician_has_role_queue_schema($pdo) ? (int)($wo['assigned_role_id'] ?? 0) : 0;
$can_edit = $is_admin || ($assigned_role_id > 0 && $assigned_role_id === $role_id);

// #region agent log
tech_dbg('H_ACCESS', 'modules/technician/view.php:access', 'Computed access', [
    'wo_id' => $wo_id,
    'session_role_id' => $role_id,
    'assigned_role_id' => $assigned_role_id,
    'is_admin' => $is_admin,
    'can_edit' => $can_edit,
]);
// #endregion

$checklist = get_checklist_for_work_order($pdo, $wo_id);
$safety_checks = get_safety_checks_for_work_order($pdo, $wo_id);
$time_logs = get_time_logs($pdo, $wo_id);
$total_time = calculate_total_time($time_logs);
$notes = get_work_order_notes($pdo, $wo_id);
$media = get_work_order_media($pdo, $wo_id);
$parts = get_work_order_parts($pdo, $wo_id);
$signoff = get_work_order_signoff($pdo, $wo_id);

require __DIR__ . '/view.view.php';
require_once __DIR__ . '/../../includes/footer.php';
?>