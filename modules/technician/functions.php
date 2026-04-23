<?php
// modules/technician/functions.php
// All database queries and helpers for the Technician Operations module.
// $pdo is provided by the hub; never create a new connection here.

// ── Work Order Listing ───────────────────────────────────────

function tech_dbg(string $hypothesisId, string $location, string $message, array $data = [], string $runId = 'pre-fix'): void {
    try {
        $row = [
            'sessionId' => '30aee9',
            'runId' => $runId,
            'hypothesisId' => $hypothesisId,
            'location' => $location,
            'message' => $message,
            'data' => $data,
            'timestamp' => (int)floor(microtime(true) * 1000),
        ];
        @file_put_contents(__DIR__ . '/../../debug-30aee9.log', json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
    } catch (Throwable $e) {}
}

function tech_table_exists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function technician_has_role_queue_schema(PDO $pdo): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'work_orders'
              AND COLUMN_NAME = 'assigned_role_id'
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function tech_is_admin_role(PDO $pdo): bool {
    $role = null;
    if (function_exists('current_role_name')) {
        try { $role = current_role_name($pdo); } catch (Throwable $e) { $role = null; }
    }
    return in_array((string)$role, ['admin', 'super_admin'], true);
}

function get_all_queue_work_orders(PDO $pdo): array {
    // Everyone can view all work orders (queue-without-claim list).
    // Use ticket.priority (NOT work_orders.priority; that column does not exist in schema).
    try {
        if (!technician_has_role_queue_schema($pdo)) {
            $stmt = $pdo->prepare("
                SELECT wo.wo_id, wo.wo_number, wo.status,
                       NULL AS assigned_role_id,
                       wo.assigned_to,
                       COALESCE(t.priority, 'medium') AS priority,
                       t.title AS ticket_title, t.description AS ticket_description,
                       wo.notes, wo.wo_type,
                       l.building, l.floor, l.room,
                       u.full_name AS requester_name, u.contact_number, u.email,
                       assigned_user.full_name AS assigned_to_name,
                       wo.created_at, wo.scheduled_end
                FROM work_orders wo
                LEFT JOIN tickets t ON wo.ticket_id = t.ticket_id
                LEFT JOIN locations l ON t.location_id = l.location_id
                LEFT JOIN users u ON t.requester_id = u.user_id
                LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
                ORDER BY wo.created_at DESC
                LIMIT 250
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $pdo->prepare("
            SELECT wo.wo_id, wo.wo_number, wo.status,
                   wo.assigned_role_id,
                   wo.assigned_to,
                   COALESCE(t.priority, 'medium') AS priority,
                   t.title AS ticket_title, t.description AS ticket_description,
                   wo.notes, wo.wo_type,
                   l.building, l.floor, l.room,
                   u.full_name AS requester_name, u.contact_number, u.email,
                   assigned_user.full_name AS assigned_to_name,
                   wo.created_at, wo.scheduled_end
            FROM work_orders wo
            LEFT JOIN tickets t ON wo.ticket_id = t.ticket_id
            LEFT JOIN locations l ON t.location_id = l.location_id
            LEFT JOIN users u ON t.requester_id = u.user_id
            LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
            ORDER BY wo.created_at DESC
            LIMIT 250
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_SQL', 'modules/technician/functions.php:get_all_queue_work_orders', 'List query failed', [
            'msg' => $e->getMessage(),
        ]);
        return [];
    }
}

function get_assigned_work_orders(PDO $pdo, int $technician_id): array {
    $stmt = $pdo->prepare("
        SELECT wo.wo_id, wo.wo_number, wo.status,
               COALESCE(t.priority, 'medium') AS priority,
               t.title AS ticket_title, t.description AS ticket_description,
               wo.notes, wo.wo_type,
               l.building, l.floor, l.room,
               u.full_name AS requester_name, u.contact_number, u.email,
               assigned_user.full_name AS assigned_to_name,
               wo.assigned_to,
               wo.created_at, wo.scheduled_end
        FROM work_orders wo
        LEFT JOIN tickets t ON wo.ticket_id = t.ticket_id
        LEFT JOIN locations l ON t.location_id = l.location_id
        LEFT JOIN users u ON t.requester_id = u.user_id
        LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
        WHERE wo.assigned_to = ? AND wo.status IN ('assigned', 'in_progress', 'scheduled', 'resolved', 'closed')
        ORDER BY FIELD(COALESCE(t.priority, 'medium'), 'critical', 'high', 'medium', 'low'), wo.created_at DESC
    ");
    $stmt->execute([$technician_id]);
    return $stmt->fetchAll();
}

function get_work_order_stats(PDO $pdo, int $technician_id): array {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'assigned') AS pending,
            SUM(status = 'in_progress') AS in_progress,
            SUM(status = 'resolved') AS completed
        FROM work_orders
        WHERE assigned_to = ? AND status IN ('assigned', 'scheduled', 'in_progress', 'resolved', 'closed')
    ");
    $stmt->execute([$technician_id]);
    return $stmt->fetch();
}

// ── Work Order Detail ───────────────────────────────────────

function get_work_order_detail(PDO $pdo, int $wo_id): ?array {
    $stmt = $pdo->prepare("
        SELECT wo.*,
               COALESCE(t.priority, 'medium') AS priority,
               t.title AS ticket_title, t.description AS ticket_description,
               l.building, l.floor, l.room,
               u.full_name AS requester_name, u.contact_number, u.email,
               assigned_user.full_name AS assigned_to_name,
               ac.category_name,
               a.asset_tag, a.serial_number, a.manufacturer, a.model
        FROM work_orders wo
        LEFT JOIN tickets t ON wo.ticket_id = t.ticket_id
        LEFT JOIN locations l ON t.location_id = l.location_id
        LEFT JOIN users u ON t.requester_id = u.user_id
        LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
        LEFT JOIN assets a ON t.asset_id = a.asset_id
        LEFT JOIN asset_categories ac ON a.category_id = ac.category_id
        WHERE wo.wo_id = ?
    ");
    $stmt->execute([$wo_id]);
    $row = $stmt->fetch() ?: null;
    // #region agent log
    tech_dbg('H_PRIORITY', 'modules/technician/functions.php:get_work_order_detail', 'Fetched detail row', [
        'wo_id' => $wo_id,
        'has_row' => (bool)$row,
        'has_priority_key' => is_array($row) && array_key_exists('priority', $row),
        'priority' => is_array($row) ? ($row['priority'] ?? null) : null,
    ]);
    // #endregion
    return $row;
}

// ── Checklist ───────────────────────────────────────────────

function get_checklist_for_work_order(PDO $pdo, int $wo_id): array {
    try {
        // First, get the asset category from the ticket
        $stmt = $pdo->prepare("
            SELECT ac.category_id
            FROM work_orders wo
            JOIN tickets t ON wo.ticket_id = t.ticket_id
            LEFT JOIN assets a ON t.asset_id = a.asset_id
            LEFT JOIN asset_categories ac ON a.category_id = ac.category_id
            WHERE wo.wo_id = ?
        ");
        $stmt->execute([$wo_id]);
        $category_id = $stmt->fetchColumn();

        // Get checklist for category, or general if none
        $stmt = $pdo->prepare("
            SELECT checklist_id FROM wo_checklists
            WHERE category_id = ? OR category_id IS NULL
            ORDER BY category_id DESC LIMIT 1
        ");
        $stmt->execute([$category_id]);
        $checklist_id = $stmt->fetchColumn();

        // If no checklist found, get the General Repair Checklist (category_id IS NULL)
        if (!$checklist_id) {
            $stmt = $pdo->prepare("
                SELECT checklist_id FROM wo_checklists 
                WHERE category_id IS NULL LIMIT 1
            ");
            $stmt->execute();
            $checklist_id = $stmt->fetchColumn();
        }

        // If still no checklist, return default General Repair Checklist items with verification info
        if (!$checklist_id) {
            return [
                ['item_id' => 1, 'item_text' => 'Capture before-repair photo', 'is_mandatory' => 1, 'requires_photo' => 1, 'is_verifiable' => 1, 'verification_type' => 'photo_before', 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 2, 'item_text' => 'Perform visual inspection', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 3, 'item_text' => 'Perform power-on test', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 4, 'item_text' => 'Verify core functionality', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 5, 'item_text' => 'Document findings and actions', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 6, 'item_text' => 'Capture after-repair photo', 'is_mandatory' => 1, 'requires_photo' => 1, 'is_verifiable' => 1, 'verification_type' => 'photo_after', 'is_done' => 0, 'notes' => null, 'completed_at' => null]
            ];
        }

        // Get items - check if is_verifiable and verification_type columns exist
        $columnsCheck = $pdo->query("SHOW COLUMNS FROM wo_checklist_items LIKE 'is_verifiable'")->fetch();
        $hasVerificationColumns = (bool)$columnsCheck;
    
    if ($hasVerificationColumns) {
        $stmt = $pdo->prepare("
            SELECT i.item_id, i.item_text, i.is_mandatory, i.requires_photo,
                   i.is_verifiable, i.verification_type,
                   COALESCE(c.is_done, 0) AS is_done, c.notes, c.completed_at
            FROM wo_checklist_items i
            LEFT JOIN wo_checklist_completions c ON i.item_id = c.item_id AND c.wo_id = ?
            WHERE i.checklist_id = ?
            ORDER BY i.sort_order
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT i.item_id, i.item_text, i.is_mandatory, i.requires_photo,
                   0 AS is_verifiable, NULL AS verification_type,
                   COALESCE(c.is_done, 0) AS is_done, c.notes, c.completed_at
            FROM wo_checklist_items i
            LEFT JOIN wo_checklist_completions c ON i.item_id = c.item_id AND c.wo_id = ?
            WHERE i.checklist_id = ?
            ORDER BY i.sort_order
        ");
    }
        $stmt->execute([$wo_id, $checklist_id]);
        $results = $stmt->fetchAll();
        
        // If no checklist items found, return defaults with verification info
        if (empty($results)) {
            return [
                ['item_id' => 1, 'item_text' => 'Capture before-repair photo', 'is_mandatory' => 1, 'requires_photo' => 1, 'is_verifiable' => 1, 'verification_type' => 'photo_before', 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 2, 'item_text' => 'Perform visual inspection', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 3, 'item_text' => 'Perform power-on test', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 4, 'item_text' => 'Verify core functionality', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 5, 'item_text' => 'Document findings and actions', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['item_id' => 6, 'item_text' => 'Capture after-repair photo', 'is_mandatory' => 1, 'requires_photo' => 1, 'is_verifiable' => 1, 'verification_type' => 'photo_after', 'is_done' => 0, 'notes' => null, 'completed_at' => null]
            ];
        }
        
        return $results;
    } catch (Exception $e) {
        // If any query fails, return default checklist items
        return [
            ['item_id' => 1, 'item_text' => 'Capture before-repair photo', 'is_mandatory' => 1, 'requires_photo' => 1, 'is_verifiable' => 1, 'verification_type' => 'photo_before', 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['item_id' => 2, 'item_text' => 'Perform visual inspection', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['item_id' => 3, 'item_text' => 'Perform power-on test', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['item_id' => 4, 'item_text' => 'Verify core functionality', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['item_id' => 5, 'item_text' => 'Document findings and actions', 'is_mandatory' => 1, 'requires_photo' => 0, 'is_verifiable' => 0, 'verification_type' => null, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['item_id' => 6, 'item_text' => 'Capture after-repair photo', 'is_mandatory' => 1, 'requires_photo' => 1, 'is_verifiable' => 1, 'verification_type' => 'photo_after', 'is_done' => 0, 'notes' => null, 'completed_at' => null]
        ];
    }
}

// ── Safety Checks ───────────────────────────────────────────

function get_safety_checks_for_work_order(PDO $pdo, int $wo_id): array {
    try {
        // Check if safety checks table exists
        $table_exists = $pdo->query("SHOW TABLES LIKE 'wo_safety_checks'")->fetch();
        
        if (!$table_exists) {
            // Return default safety checks if table doesn't exist
            return [
                ['safety_id' => 1, 'safety_text' => 'ESD mat connected and grounded', 'is_mandatory' => 1, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['safety_id' => 2, 'safety_text' => 'Equipment powered off before inspection', 'is_mandatory' => 1, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['safety_id' => 3, 'safety_text' => 'Ladder secured and stable (if used)', 'is_mandatory' => 1, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['safety_id' => 4, 'safety_text' => 'Personal protective equipment worn', 'is_mandatory' => 0, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
                ['safety_id' => 5, 'safety_text' => 'Work area clear of hazards', 'is_mandatory' => 0, 'is_done' => 0, 'notes' => null, 'completed_at' => null]
            ];
        }
        
        $stmt = $pdo->prepare("
            SELECT s.safety_id, s.safety_text, s.is_mandatory,
                   COALESCE(c.is_done, 0) AS is_done, c.notes, c.completed_at
            FROM wo_safety_checks s
            LEFT JOIN wo_safety_completions c ON s.safety_id = c.safety_id AND c.wo_id = ?
            ORDER BY s.sort_order
        ");
        $stmt->execute([$wo_id]);
        $results = $stmt->fetchAll();
    } catch (Exception $e) {
        // If query fails, return default safety checks
        return [
            ['safety_id' => 1, 'safety_text' => 'ESD mat connected and grounded', 'is_mandatory' => 1, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['safety_id' => 2, 'safety_text' => 'Equipment powered off before inspection', 'is_mandatory' => 1, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['safety_id' => 3, 'safety_text' => 'Ladder secured and stable (if used)', 'is_mandatory' => 1, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['safety_id' => 4, 'safety_text' => 'Personal protective equipment worn', 'is_mandatory' => 0, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['safety_id' => 5, 'safety_text' => 'Work area clear of hazards', 'is_mandatory' => 0, 'is_done' => 0, 'notes' => null, 'completed_at' => null]
        ];
    }
    
    // If no safety checks in database, return defaults
    if (empty($results)) {
        return [
            ['safety_id' => 1, 'safety_text' => 'ESD mat connected and grounded', 'is_mandatory' => 1, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['safety_id' => 2, 'safety_text' => 'Equipment powered off before inspection', 'is_mandatory' => 1, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['safety_id' => 3, 'safety_text' => 'Ladder secured and stable (if used)', 'is_mandatory' => 1, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['safety_id' => 4, 'safety_text' => 'Personal protective equipment worn', 'is_mandatory' => 0, 'is_done' => 0, 'notes' => null, 'completed_at' => null],
            ['safety_id' => 5, 'safety_text' => 'Work area clear of hazards', 'is_mandatory' => 0, 'is_done' => 0, 'notes' => null, 'completed_at' => null]
        ];
    }
    
    return $results;
}

function update_safety_completion(PDO $pdo, int $wo_id, int $safety_id, bool $is_done, ?string $notes = null): void {
    if ($is_done) {
        $stmt = $pdo->prepare("
            INSERT INTO wo_safety_completions (wo_id, safety_id, is_done, notes, completed_by, completed_at)
            VALUES (?, ?, 1, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE is_done = 1, notes = VALUES(notes), completed_by = VALUES(completed_by), completed_at = NOW()
        ");
        $stmt->execute([$wo_id, $safety_id, $notes, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM wo_safety_completions WHERE wo_id = ? AND safety_id = ?
        ");
        $stmt->execute([$wo_id, $safety_id]);
    }
}

// ── Time Logs ───────────────────────────────────────────────

function get_time_logs(PDO $pdo, int $wo_id): array {
    $stmt = $pdo->prepare("
        SELECT * FROM wo_time_logs
        WHERE wo_id = ?
        ORDER BY logged_at ASC
    ");
    $stmt->execute([$wo_id]);
    return $stmt->fetchAll();
}

function calculate_total_time(array $logs): int {
    $total = 0;
    $start = null;
    foreach ($logs as $log) {
        if ($log['action'] === 'start' || $log['action'] === 'resume') {
            $start = strtotime($log['logged_at']);
        } elseif (($log['action'] === 'pause' || $log['action'] === 'stop') && $start) {
            $total += strtotime($log['logged_at']) - $start;
            $start = null;
        }
    }
    return $total;
}

// ── Notes ───────────────────────────────────────────────────

function get_work_order_notes(PDO $pdo, int $wo_id): array {
    if (!tech_table_exists($pdo, 'wo_notes')) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_notes', 'Missing table wo_notes', ['wo_id' => $wo_id]);
        return [];
    }
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, u.full_name AS added_by_name
            FROM wo_notes n
            LEFT JOIN users u ON n.added_by = u.user_id
            WHERE n.wo_id = ?
            ORDER BY n.added_at ASC
        ");
        $stmt->execute([$wo_id]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_notes', 'wo_notes query failed', ['wo_id' => $wo_id]);
        return [];
    }
}

// ── Media ───────────────────────────────────────────────────

function get_work_order_media(PDO $pdo, int $wo_id): array {
    if (!tech_table_exists($pdo, 'wo_media')) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_media', 'Missing table wo_media', ['wo_id' => $wo_id]);
        return [];
    }
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM wo_media
            WHERE wo_id = ?
            ORDER BY uploaded_at ASC
        ");
        $stmt->execute([$wo_id]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_media', 'wo_media query failed', ['wo_id' => $wo_id]);
        return [];
    }
}

// ── Parts ───────────────────────────────────────────────────

function get_work_order_parts(PDO $pdo, int $wo_id): array {
    if (!tech_table_exists($pdo, 'wo_parts_used')) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_parts', 'Missing table wo_parts_used', ['wo_id' => $wo_id]);
        return [];
    }
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, pi.part_name, pi.part_number
            FROM wo_parts_used p
            JOIN parts_inventory pi ON p.part_id = pi.part_id
            WHERE p.wo_id = ?
            ORDER BY p.used_at ASC
        ");
        $stmt->execute([$wo_id]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_parts', 'wo_parts_used query failed', ['wo_id' => $wo_id]);
        return [];
    }
}

// ── Sign-off ────────────────────────────────────────────────

function get_work_order_signoff(PDO $pdo, int $wo_id): ?array {
    if (!tech_table_exists($pdo, 'wo_signoff')) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_signoff', 'Missing table wo_signoff', ['wo_id' => $wo_id]);
        return null;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM wo_signoff WHERE wo_id = ?");
        $stmt->execute([$wo_id]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        tech_dbg('H_DB', 'modules/technician/functions.php:get_work_order_signoff', 'wo_signoff query failed', ['wo_id' => $wo_id]);
        return null;
    }
}

// ── Save Functions ──────────────────────────────────────────

function save_time_log(PDO $pdo, int $wo_id, int $technician_id, string $action, ?string $labor_type = null, ?string $notes = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, notes)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$wo_id, $technician_id, $action, $labor_type, $notes]);
}

function update_checklist_completion(PDO $pdo, int $wo_id, int $item_id, bool $is_done, ?string $notes = null): void {
    if ($is_done) {
        $stmt = $pdo->prepare("
            INSERT INTO wo_checklist_completions (wo_id, item_id, is_done, notes, completed_by, completed_at)
            VALUES (?, ?, 1, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE is_done = 1, notes = VALUES(notes), completed_by = VALUES(completed_by), completed_at = NOW()
        ");
        $stmt->execute([$wo_id, $item_id, $notes, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM wo_checklist_completions WHERE wo_id = ? AND item_id = ?
        ");
        $stmt->execute([$wo_id, $item_id]);
    }
}

function add_work_order_note(PDO $pdo, int $wo_id, string $note_text, bool $is_voice = false, ?string $voice_path = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_notes (wo_id, note_text, is_voice, voice_path, added_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$wo_id, $note_text, $is_voice ? 1 : 0, $voice_path, $_SESSION['user_id']]);
}

function save_work_order_media(PDO $pdo, int $wo_id, string $media_type, string $file_path, string $file_type, int $file_size_kb, ?string $caption = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_media (wo_id, media_type, file_path, file_type, file_size_kb, caption, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$wo_id, $media_type, $file_path, $file_type, $file_size_kb, $caption, $_SESSION['user_id']]);
}

function save_work_order_part(PDO $pdo, int $wo_id, int $part_id, int $quantity_used, ?string $serial_number = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_parts_used (wo_id, part_id, quantity_used, serial_number, used_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$wo_id, $part_id, $quantity_used, $serial_number, $_SESSION['user_id']]);

    // Decrement inventory
    $pdo->prepare("UPDATE parts_inventory SET quantity_on_hand = quantity_on_hand - ? WHERE part_id = ?")
        ->execute([$quantity_used, $part_id]);
}

function save_work_order_signoff(PDO $pdo, int $wo_id, string $signer_name, string $signature_path, ?int $satisfaction = null, ?string $feedback = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO wo_signoff (wo_id, signer_name, signature_path, satisfaction, feedback, signed_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE signer_name = VALUES(signer_name), signature_path = VALUES(signature_path),
                               satisfaction = VALUES(satisfaction), feedback = VALUES(feedback)
    ");
    $stmt->execute([$wo_id, $signer_name, $signature_path, $satisfaction, $feedback, $_SESSION['user_id']]);

    // Mark WO as resolved
    $pdo->prepare("UPDATE work_orders SET status = 'resolved', actual_end = NOW() WHERE wo_id = ?")
        ->execute([$wo_id]);
}

function update_work_order_status(PDO $pdo, int $wo_id, string $status): void {
    $update = [];
    $params = [$status, $wo_id];

    if ($status === 'in_progress') {
        $update[] = 'actual_start = NOW()';
    } elseif ($status === 'resolved') {
        $update[] = 'actual_end = NOW()';
    }

    $sql = "UPDATE work_orders SET status = ?" . (count($update) ? ', ' . implode(', ', $update) : '') . " WHERE wo_id = ?";
    $pdo->prepare($sql)->execute($params);
}

function can_complete_work_order(PDO $pdo, int $wo_id): array {
    $errors = [];

    // Check safety checks
    $safety_checks = get_safety_checks_for_work_order($pdo, $wo_id);
    $mandatory_safety = array_filter($safety_checks, fn($s) => $s['is_mandatory']);
    $incomplete_safety = array_filter($mandatory_safety, fn($s) => !$s['is_done']);
    if ($incomplete_safety) {
        $errors[] = 'All mandatory safety checks must be completed';
    }

    // Check checklist
    $checklist = get_checklist_for_work_order($pdo, $wo_id);
    $mandatory_checklist = array_filter($checklist, fn($c) => $c['is_mandatory']);
    $incomplete_checklist = array_filter($mandatory_checklist, fn($c) => !$c['is_done']);
    if ($incomplete_checklist) {
        $errors[] = 'All mandatory checklist items must be completed';
    }

    // Check media (at least one before or after)
    $media = get_work_order_media($pdo, $wo_id);
    $has_media = count($media) > 0;
    if (!$has_media) {
        $errors[] = 'At least one photo or video must be attached';
    }

    // Check signoff
    $signoff = get_work_order_signoff($pdo, $wo_id);
    if (!$signoff) {
        $errors[] = 'Requester signature and satisfaction rating are required';
    }

    // Check time tracking started
    $time_logs = get_time_logs($pdo, $wo_id);
    if (empty($time_logs)) {
        $errors[] = 'Time tracking must be started';
    }

    return $errors;
}
?>
