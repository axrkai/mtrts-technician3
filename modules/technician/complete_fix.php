<?php
/**
 * Complete Technician Module Fix
 * Ensures safety checks and checklist items display properly for manual checking
 * Based on Module 4 requirements from instructions
 */

require_once __DIR__ . '/../../config/guard.php';

echo "<h1>Complete Technician Module Fix</h1>\n";

try {
    // Step 1: Verify safety checks table and data
    echo "<h2>Step 1: Verify Safety Checks</h2>\n";
    
    $safety_count = $pdo->query("SELECT COUNT(*) FROM wo_safety_checks")->fetchColumn();
    echo "Safety checks in database: $safety_count<br>";
    
    if ($safety_count > 0) {
        $safety_items = $pdo->query("SELECT safety_id, check_text, is_mandatory FROM wo_safety_checks ORDER BY sort_order")->fetchAll();
        echo "<h3>Safety Check Items:</h3>";
        echo "<ul>";
        foreach ($safety_items as $item) {
            $mandatory = $item['is_mandatory'] ? ' (Required)' : ' (Optional)';
            echo "<li>" . htmlspecialchars($item['check_text']) . $mandatory . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<span style='color: red;'>No safety checks found</span><br>";
    }
    
    // Step 2: Verify checklist table and data
    echo "<h2>Step 2: Verify Checklist Items</h2>\n";
    
    $checklist_count = $pdo->query("SELECT COUNT(*) FROM wo_checklist_items")->fetchColumn();
    echo "Checklist items in database: $checklist_count<br>";
    
    if ($checklist_count > 0) {
        $checklist_items = $pdo->query("SELECT item_id, item_text, is_mandatory, requires_photo FROM wo_checklist_items ORDER BY checklist_id, sort_order")->fetchAll();
        echo "<h3>Checklist Items:</h3>";
        echo "<ul>";
        foreach ($checklist_items as $item) {
            $mandatory = $item['is_mandatory'] ? ' (Required)' : ' (Optional)';
            $photo = $item['requires_photo'] ? ' (Photo Required)' : '';
            echo "<li>" . htmlspecialchars($item['item_text']) . $mandatory . $photo . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<span style='color: red;'>No checklist items found</span><br>";
    }
    
    // Step 3: Test with a work order
    echo "<h2>Step 3: Test Work Order Loading</h2>\n";
    
    $wo = $pdo->query("SELECT wo_id FROM work_orders LIMIT 1")->fetch();
    if ($wo) {
        require_once __DIR__ . '/functions.php';
        
        $safety_checks = get_safety_checks_for_work_order($pdo, $wo['wo_id']);
        $checklist = get_checklist_for_work_order($pdo, $wo['wo_id']);
        
        echo "<h3>Work Order {$wo['wo_id']} Test Results:</h3>";
        echo "Safety checks loaded: " . count($safety_checks) . "<br>";
        echo "Checklist items loaded: " . count($checklist) . "<br>";
        
        if (count($safety_checks) > 0) {
            echo "<h4>Safety Checks Found:</h4>";
            foreach ($safety_checks as $safety) {
                $status = $safety['is_done'] ? '✅' : '❌';
                echo "<div>$status " . htmlspecialchars($safety['safety_text']) . "</div>";
            }
        }
        
        if (count($checklist) > 0) {
            echo "<h4>Checklist Items Found:</h4>";
            foreach ($checklist as $item) {
                $status = $item['is_done'] ? '✅' : '❌';
                $photo = $item['requires_photo'] ? ' (📷)' : '';
                echo "<div>$status " . htmlspecialchars($item['item_text']) . $photo . "</div>";
            }
        }
    }
    
    echo "<h2>Step 4: Manual Database Insert (if needed)</h2>\n";
    echo "<p>If tables are empty, this script will insert the required data:</p>";
    
    // Insert safety checks if empty
    if ($safety_count == 0) {
        echo "<h3>Inserting Safety Checks...</h3>";
        $pdo->exec("
            INSERT INTO wo_safety_checks (check_text, is_mandatory, sort_order) VALUES
            ('ESD mat connected and grounded', 1, 1),
            ('Equipment powered off before inspection', 1, 2),
            ('Ladder secured and stable (if used)', 1, 3),
            ('Personal protective equipment worn', 0, 4),
            ('Work area clear of hazards', 0, 5)
        ");
        echo "Safety checks inserted successfully<br>";
    }
    
    // Insert checklist items if empty
    if ($checklist_count == 0) {
        echo "<h3>Inserting Checklist Items...</h3>";
        
        // Get or create General Repair Checklist
        $checklist_id = $pdo->query("SELECT checklist_id FROM wo_checklists WHERE category_id IS NULL LIMIT 1")->fetchColumn();
        if (!$checklist_id) {
            $pdo->exec("INSERT INTO wo_checklists (category_id, checklist_name, description) VALUES (NULL, 'General Repair Checklist', 'Generic checklist for any equipment type')");
            $checklist_id = $pdo->lastInsertId();
        }
        
        $pdo->exec("
            INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, sort_order) VALUES
            ($checklist_id, 'Capture before-repair photo', 1, 1, 1),
            ($checklist_id, 'Perform visual inspection', 1, 0, 2),
            ($checklist_id, 'Perform power-on test', 1, 0, 3),
            ($checklist_id, 'Verify core functionality', 1, 0, 4),
            ($checklist_id, 'Document findings and actions', 1, 0, 5),
            ($checklist_id, 'Capture after-repair photo', 1, 1, 6)
        ");
        echo "Checklist items inserted successfully<br>";
    }
    
    echo "<h2 style='color: green;'>✅ COMPLETE FIX APPLIED</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Refresh your technician module work order page</li>";
    echo "<li>Safety checks should now show items to check off</li>";
    echo "<li>Checklist should now show items to check off</li>";
    echo "<li>You should be able to start work and complete the full workflow</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERROR: " . $e->getMessage() . "</h2>";
}
?>
