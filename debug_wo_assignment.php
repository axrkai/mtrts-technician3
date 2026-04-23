<?php
// Simple debugging script to check work order assignment
// This can be accessed via web browser

echo "<h2>Work Order Assignment Debug</h2>";

// Check if we can connect to database
try {
    require_once 'config/database.php';
    $pdo = getPDO();
    echo "<p>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Check for WO-2026-0001 specifically
echo "<h3>Checking WO-2026-0001:</h3>";

$stmt = $pdo->prepare("
    SELECT wo.wo_id, wo.wo_number, wo.assigned_to, assigned_user.full_name AS assigned_to_name, 
           assigned_user.email, assigned_user.is_active, u.full_name AS requester_name
    FROM work_orders wo
    LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
    LEFT JOIN users u ON wo.assigned_by = u.user_id
    WHERE wo.wo_number = 'WO-2026-0001'
");
$stmt->execute();
$wo = $stmt->fetch();

if ($wo) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>wo_id</td><td>" . htmlspecialchars($wo['wo_id']) . "</td></tr>";
    echo "<tr><td>wo_number</td><td>" . htmlspecialchars($wo['wo_number']) . "</td></tr>";
    echo "<tr><td>assigned_to</td><td>" . htmlspecialchars($wo['assigned_to'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>assigned_to_name</td><td>" . htmlspecialchars($wo['assigned_to_name'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>assigned_user.email</td><td>" . htmlspecialchars($wo['email'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>assigned_user.is_active</td><td>" . htmlspecialchars($wo['is_active'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>requester_name</td><td>" . htmlspecialchars($wo['requester_name'] ?? 'NULL') . "</td></tr>";
    echo "</table>";
    
    // Check if the assigned user exists
    if ($wo['assigned_to']) {
        echo "<h4>Checking assigned user details:</h4>";
        $user_check = $pdo->prepare("SELECT user_id, full_name, email, is_active, role_id FROM users WHERE user_id = ?");
        $user_check->execute([$wo['assigned_to']]);
        $user = $user_check->fetch();
        
        if ($user) {
            echo "<p>✓ User found in database</p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>User Field</th><th>Value</th></tr>";
            echo "<tr><td>user_id</td><td>" . htmlspecialchars($user['user_id']) . "</td></tr>";
            echo "<tr><td>full_name</td><td>" . htmlspecialchars($user['full_name']) . "</td></tr>";
            echo "<tr><td>email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
            echo "<tr><td>is_active</td><td>" . htmlspecialchars($user['is_active']) . "</td></tr>";
            echo "<tr><td>role_id</td><td>" . htmlspecialchars($user['role_id']) . "</td></tr>";
            echo "</table>";
        } else {
            echo "<p>✗ User with ID " . htmlspecialchars($wo['assigned_to']) . " not found in users table!</p>";
        }
    } else {
        echo "<p>⚠ Work order is not assigned to anyone (assigned_to is NULL)</p>";
    }
} else {
    echo "<p>✗ WO-2026-0001 not found in database</p>";
}

// Show all work orders with assignment info
echo "<h3>All Work Orders (first 10):</h3>";
$stmt = $pdo->prepare("
    SELECT wo.wo_number, wo.assigned_to, assigned_user.full_name AS assigned_to_name
    FROM work_orders wo
    LEFT JOIN users assigned_user ON wo.assigned_to = assigned_user.user_id
    ORDER BY wo.wo_id DESC
    LIMIT 10
");
$stmt->execute();
$work_orders = $stmt->fetchAll();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>WO Number</th><th>assigned_to</th><th>assigned_to_name</th></tr>";
foreach ($work_orders as $wo) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($wo['wo_number']) . "</td>";
    echo "<td>" . htmlspecialchars($wo['assigned_to'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($wo['assigned_to_name'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check available technicians
echo "<h3>Available Technicians:</h3>";
$stmt = $pdo->prepare("
    SELECT u.user_id, u.full_name, u.email, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE r.role_name IN ('it_staff', 'technician') AND u.is_active = 1
    ORDER BY u.full_name
");
$stmt->execute();
$technicians = $stmt->fetchAll();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>User ID</th><th>Full Name</th><th>Email</th><th>Role</th></tr>";
foreach ($technicians as $tech) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($tech['user_id']) . "</td>";
    echo "<td>" . htmlspecialchars($tech['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($tech['email']) . "</td>";
    echo "<td>" . htmlspecialchars($tech['role_name']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
