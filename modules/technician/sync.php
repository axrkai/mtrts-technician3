<?php
$module = 'technician';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

// File validation rules (must match frontend idb-storage.js)
define('FILE_RULES', [
    'config' => [
        'maxSize' => 50 * 1024 * 1024, // 50MB
        'extensions' => ['.json', '.xml', '.cfg', '.conf', '.ini', '.txt'],
    ],
    'log' => [
        'maxSize' => 50 * 1024 * 1024, // 50MB
        'extensions' => ['.log', '.txt', '.csv'],
    ],
    'backup' => [
        'maxSize' => 50 * 1024 * 1024, // 50MB
        'extensions' => ['.zip', '.tar', '.gz', '.bak', '.img'],
    ],
    'image' => [
        'maxSize' => 20 * 1024 * 1024, // 20MB
        'extensions' => ['.jpg', '.jpeg', '.png', '.gif', '.webp'],
    ],
    'video' => [
        'maxSize' => 100 * 1024 * 1024, // 100MB
        'extensions' => ['.mp4', '.webm', '.mov'],
    ],
]);

// Get all allowed extensions for config uploads
function getAllowedConfigExtensions() {
    return array_unique(array_merge(
        FILE_RULES['config']['extensions'],
        FILE_RULES['log']['extensions'],
        FILE_RULES['backup']['extensions']
    ));
}

// Validate uploaded file
function validateUploadedFile($file, $fileType = 'image') {
    $filename = $file['name'] ?? '';
    $size = $file['size'] ?? 0;
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    
    // Check for upload errors
    if ($error !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server maximum size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        ];
        return ['valid' => false, 'error' => $errorMessages[$error] ?? 'Upload error'];
    }
    
    // Get file extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $ext = $ext ? '.' . $ext : '';
    
    // For config type, check against all config-related extensions
    if ($fileType === 'config') {
        $allowedExts = getAllowedConfigExtensions();
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        if (!in_array($ext, $allowedExts)) {
            return [
                'valid' => false, 
                'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExts)
            ];
        }
    } else {
        // Check specific file type rules
        $rules = FILE_RULES[$fileType] ?? FILE_RULES['image'];
        $allowedExts = $rules['extensions'];
        $maxSize = $rules['maxSize'];
        
        if (!in_array($ext, $allowedExts)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExts)
            ];
        }
    }
    
    // Check file size
    if ($size > $maxSize) {
        $maxMB = round($maxSize / 1024 / 1024);
        return ['valid' => false, 'error' => "File exceeds maximum size of {$maxMB}MB"];
    }
    
    // Additional security: check for dangerous extensions
    $dangerousExts = ['.php', '.phtml', '.php3', '.php4', '.php5', '.exe', '.sh', '.bat', '.cmd', '.js', '.html', '.htm'];
    if (in_array($ext, $dangerousExts)) {
        return ['valid' => false, 'error' => 'File type not allowed for security reasons'];
    }
    
    return ['valid' => true, 'extension' => $ext];
}

// Handle both JSON and FormData (multipart) requests
if ($_SERVER['CONTENT_TYPE'] && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
  // JSON payload
  $payload = json_decode(file_get_contents('php://input'), true) ?: [];
  $action = $payload['action'] ?? '';
} else {
  // FormData payload - check for specific actions first
  $action = $_POST['action'] ?? 'batch_sync';
}

switch ($action) {
    case 'batch_sync':
        // Handle multipart FormData batch sync for evidence/config files with blobs
        $results = [];
        
        // Find all item_* fields and group by item ID
        $itemIds = [];
        foreach ($_POST as $key => $value) {
          if (preg_match('/^item_(.+?)_/', $key, $m)) {
            $itemIds[$m[1]] = true;
          }
        }
        
        foreach (array_keys($itemIds) as $itemId) {
          $itemAction = $_POST["item_${itemId}_action"] ?? '';
          $woId = (int)($_POST["item_${itemId}_wo_id"] ?? 0);
          
          if ($itemAction === 'evidence_add') {
            $side = $_POST["item_${itemId}_side"] ?? '';
            $kind = $_POST["item_${itemId}_kind"] ?? 'image';
            $name = $_POST["item_${itemId}_name"] ?? '';
            
            if (isset($_FILES["item_${itemId}_file"])) {
              $file = $_FILES["item_${itemId}_file"];
              
              // Validate file type and size
              $validation = validateUploadedFile($file, $kind);
              if (!$validation['valid']) {
                $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'evidence_add', 'error' => $validation['error']];
                continue;
              }
              
              $upload_dir = __DIR__ . '/../../uploads/evidence/' . $woId . '/';
              @mkdir($upload_dir, 0755, true);
              
              // Sanitize filename and add timestamp to prevent collisions
              $original_name = basename($file['name']);
              $ext = $validation['extension'];
              $safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
              $filename = $safe_name . '_' . time() . $ext;
              $file_path = $upload_dir . $filename;
              
              if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $serverUrl = '/uploads/evidence/' . $woId . '/' . $filename;
                save_work_order_media($pdo, $woId, $kind, $serverUrl, $file['type'], filesize($file_path), $name);
                $results[] = ['id' => $itemId, 'ok' => true, 'action' => 'evidence_add', 'serverUrl' => $serverUrl];
              } else {
                $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'evidence_add', 'error' => 'Upload failed'];
              }
            } else {
              $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'evidence_add', 'error' => 'No file'];
            }
          } elseif ($itemAction === 'config_add') {
            $name = $_POST["item_${itemId}_name"] ?? '';
            
            if (isset($_FILES["item_${itemId}_file"])) {
              $file = $_FILES["item_${itemId}_file"];
              
              // Validate file type and size (config includes logs and backups)
              $validation = validateUploadedFile($file, 'config');
              if (!$validation['valid']) {
                $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'config_add', 'error' => $validation['error']];
                continue;
              }
              
              $upload_dir = __DIR__ . '/../../uploads/config/' . $woId . '/';
              @mkdir($upload_dir, 0755, true);
              
              // Sanitize filename and add timestamp to prevent collisions
              $original_name = basename($file['name']);
              $ext = $validation['extension'];
              $safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
              $filename = $safe_name . '_' . time() . $ext;
              $file_path = $upload_dir . $filename;
              
              if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $serverUrl = '/uploads/config/' . $woId . '/' . $filename;
                save_work_order_media($pdo, $woId, 'config', $serverUrl, $file['type'], filesize($file_path), $name);
                $results[] = ['id' => $itemId, 'ok' => true, 'action' => 'config_add', 'serverUrl' => $serverUrl];
              } else {
                $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'config_add', 'error' => 'Upload failed'];
              }
            } else {
              $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'config_add', 'error' => 'No file'];
            }
          } else {
            // Other actions like checklist_update, safety_update, etc.
            // Process them generically
            $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
          }
        }
        
        echo json_encode(['results' => $results, 'ok' => true]);
        break;

    case 'time_start':
        save_time_log($pdo, (int)($payload['wo_id'] ?? 0), $_SESSION['user_id'], 'start');
        update_work_order_status($pdo, (int)($payload['wo_id'] ?? 0), 'in_progress');
        echo json_encode(['success' => true]);
        break;

    case 'time_pause':
        save_time_log($pdo, (int)($payload['wo_id'] ?? 0), $_SESSION['user_id'], 'pause');
        echo json_encode(['success' => true]);
        break;

    case 'time_resume':
        save_time_log($pdo, (int)($payload['wo_id'] ?? 0), $_SESSION['user_id'], 'resume');
        echo json_encode(['success' => true]);
        break;

    case 'time_stop':
        // Stop creates a draft time log entry but doesn't persist yet
        echo json_encode(['success' => true, 'message' => 'Time segment stopped (draft)']);
        break;

    case 'time_log_remove':
        // Remove time log entry (draft management)
        echo json_encode(['success' => true, 'message' => 'Time entry removed']);
        break;

    case 'checklist_update':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $item_id = (int)($payload['itemId'] ?? 0);
        $is_done = (bool)($payload['completed'] ?? false);
        update_checklist_completion($pdo, $wo_id, $item_id, $is_done);
        echo json_encode(['success' => true]);
        break;

    case 'safety_update':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $safety_id = (int)($payload['safetyId'] ?? 0);
        $is_done = (bool)($payload['completed'] ?? false);
        update_safety_completion($pdo, $wo_id, $safety_id, $is_done);
        echo json_encode(['success' => true]);
        break;

    case 'note_add':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $note_text = trim($payload['text'] ?? '');
        // FIX: 4th param of add_work_order_note() is bool $is_voice, not a title string.
        // Title is not stored in wo_notes; discard it here (JS sends it for display only).
        if ($note_text) {
            add_work_order_note($pdo, $wo_id, $note_text, false);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Note text required']);
        }
        break;

    case 'note_remove':
        // Note remove is draft management, no DB action needed
        echo json_encode(['success' => true, 'message' => 'Note removed']);
        break;

    case 'part_add':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $part_number = trim($payload['partNumber'] ?? '');
        $quantity = (int)($payload['qty'] ?? 1);
        $serial = trim($payload['serial'] ?? '');

        // Find part by number
        $stmt = $pdo->prepare("SELECT part_id FROM parts_inventory WHERE part_number = ?");
        $stmt->execute([$part_number]);
        $part = $stmt->fetch();
        if ($part) {
            save_work_order_part($pdo, $wo_id, $part['part_id'], $quantity, $serial ?: null);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Part not found']);
        }
        break;

    case 'evidence_add':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $side = trim($payload['side'] ?? '');
        $kind = trim($payload['kind'] ?? 'image');
        $name = trim($payload['name'] ?? '');
        
        // Check for uploaded file in $_FILES
        if (isset($_FILES[$name])) {
            $file = $_FILES[$name];
            $upload_path = '/uploads/evidence/' . $wo_id . '/';
            @mkdir($upload_path, 0755, true);
            $file_path = $upload_path . basename($file['name']);
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                save_work_order_media($pdo, $wo_id, $kind, $file_path, $file['type'], filesize($file_path), $name);
                echo json_encode(['success' => true, 'message' => 'Evidence saved']);
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file provided']);
        }
        break;

    case 'config_add':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $name = trim($payload['name'] ?? '');
        
        // Check for uploaded file in $_FILES
        if (isset($_FILES[$name])) {
            $file = $_FILES[$name];
            $upload_path = '/uploads/config/' . $wo_id . '/';
            @mkdir($upload_path, 0755, true);
            $file_path = $upload_path . basename($file['name']);
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                save_work_order_media($pdo, $wo_id, 'config', $file_path, $file['type'], filesize($file_path), $name);
                echo json_encode(['success' => true, 'message' => 'Config file saved']);
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file provided']);
        }
        break;

    case 'config_remove':
        // For now, not implemented, as media remove not in functions
        echo json_encode(['success' => true]);
        break;

    case 'workorder_complete':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $time_logs = $payload['time_logs'] ?? [];
        $total_time_ms = (int)($payload['total_time_ms'] ?? 0);
        $signer_name = trim($payload['signer_name'] ?? '');
        
        try {
            // Persist all draft time logs to database
            if (is_array($time_logs) && count($time_logs) > 0) {
                foreach ($time_logs as $log) {
                    $stmt = $pdo->prepare("
                        INSERT INTO wo_time_logs (wo_id, labor_type, elapsed_ms, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $wo_id,
                        trim($log['labor_type'] ?? ''),
                        (int)($log['elapsed_ms'] ?? 0)
                    ]);
                }
            }
            // Mark work order as resolved
            update_work_order_status($pdo, $wo_id, 'resolved');
            echo json_encode(['success' => true, 'message' => 'Work order completed']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error completing work order: ' . $e->getMessage()]);
        }
        break;

    case 'start_work':
        $wo_id = (int)($_POST['wo_id'] ?? 0);
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        
        if ($wo_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Work order ID required']);
            break;
        }
        
        try {
            // Verify the work order exists and is assigned to this user
            $stmt = $pdo->prepare("SELECT wo_id, status, assigned_to FROM work_orders WHERE wo_id = ?");
            $stmt->execute([$wo_id]);
            $wo = $stmt->fetch();
            
            if (!$wo) {
                echo json_encode(['success' => false, 'message' => 'Work order not found']);
                break;
            }
            
            if ($wo['status'] !== 'assigned') {
                echo json_encode(['success' => false, 'message' => 'Work order must be in assigned status to start work']);
                break;
            }
            
            if ($wo['assigned_to'] != $user_id) {
                echo json_encode(['success' => false, 'message' => 'You can only start work on orders assigned to you']);
                break;
            }
            
            // Update status to in_progress
            $stmt = $pdo->prepare("
                UPDATE work_orders 
                SET status = 'in_progress', updated_at = NOW() 
                WHERE wo_id = ?
            ");
            $stmt->execute([$wo_id]);
            
            echo json_encode(['success' => true, 'message' => 'Work started successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error starting work: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

?>