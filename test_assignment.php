<?php
require_once 'config/database.php';
require_once 'modules/technician/functions.php';

$pdo = getPDO();

// Test the query directly
echo "Testing get_all_queue_work_orders function...\n";

$work_orders = get_all_queue_work_orders($pdo);

echo "Found " . count($work_orders) . " work orders\n\n";

// Look for WO-2026-0001 specifically
foreach ($work_orders as $wo) {
    if ($wo['wo_number'] === 'WO-2026-0001') {
        echo "Found WO-2026-0001:\n";
        echo "  wo_id: " . $wo['wo_id'] . "\n";
        echo "  wo_number: " . $wo['wo_number'] . "\n";
        echo "  assigned_to: " . ($wo['assigned_to'] ?? 'NULL') . "\n";
        echo "  assigned_to_name: " . ($wo['assigned_to_name'] ?? 'NULL') . "\n";
        echo "  status: " . $wo['status'] . "\n";
        break;
    }
}

// Show first few work orders for debugging
echo "\nFirst 3 work orders:\n";
for ($i = 0; $i < min(3, count($work_orders)); $i++) {
    $wo = $work_orders[$i];
    echo "  " . ($i+1) . ". " . $wo['wo_number'] . " - assigned_to: " . ($wo['assigned_to'] ?? 'NULL') . " - assigned_to_name: " . ($wo['assigned_to_name'] ?? 'NULL') . "\n";
}

// Direct SQL query test
echo "\nDirect SQL query test:\n";
$stmt = $pdo->prepare("
    SELECT wo.wo_id, wo.wo_number, wo.assigned_to, assigned_user.full_name AS assigned_to_name
    FROM work_orders wo
    LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
    WHERE wo.wo_number = 'WO-2026-0001'
");
$stmt->execute();
$result = $stmt->fetch();

if ($result) {
    echo "Direct query result for WO-2026-0001:\n";
    echo "  assigned_to: " . ($result['assigned_to'] ?? 'NULL') . "\n";
    echo "  assigned_to_name: " . ($result['assigned_to_name'] ?? 'NULL') . "\n";
} else {
    echo "WO-2026-0001 not found in direct query\n";
}
?>
